<?php

declare(strict_types=1);

/**
 * Asyntai AI Search for Bagisto: the catalogue feed.
 *
 * Bagisto has a REST API as a separate package that most stores never
 * install, so this is the store's own side of one: a single read-only
 * endpoint that hands Asyntai the products a shopper can actually see, with
 * the prices a shopper would actually pay.
 *
 * WHY A FEED AND NOT A CRAWL. Crawling a shop gives us page text, which goes
 * stale the moment a price changes and carries no stock figure at all.
 * Reading the store's own numbers is what lets the search bar show a price,
 * a discount and an availability that are correct today.
 *
 * WHAT A SHOPPER WOULD SEE IS WHAT IS SENT.
 *   * Only enabled products that are visible on their own, in the default
 *     channel and locale. A variant that only exists inside a configurable
 *     product is not listed on its own here either.
 *   * The price is the store's own minimal price for the guest group, so a
 *     special price or a catalogue rule that a shopper sees, Asyntai sees.
 *   * The price is in the store's BASE currency, because this request has no
 *     shopper and therefore no session currency.
 */

namespace Asyntai\Search;

use Illuminate\Support\Facades\DB;
use Webkul\Product\Repositories\ProductRepository;

class Feed
{
    /** Products per page. Asyntai pages through until `pages` is reached. */
    public const PAGE_SIZE = 100;

    /** Hard ceiling, so a hand-made request cannot ask for the whole shop at once. */
    public const MAX_PAGE_SIZE = 250;

    /** How far a signed request's clock may drift, in seconds. */
    public const CLOCK_SKEW = 300;

    /** Longest description we send. Asyntai truncates again; this saves bandwidth. */
    public const MAX_DESCRIPTION = 2000;

    private ProductRepository $products;

    private string $channel = '';

    private string $locale = '';

    public function __construct(ProductRepository $products)
    {
        $this->products = $products;
    }

    // -----------------------------------------------------------------
    // Entry point
    // -----------------------------------------------------------------

    /**
     * Build the answer for one feed request.
     *
     * Never throws. The caller sets the status code from `status` and sends
     * the rest as JSON.
     *
     * @param  array  $query  The request query parameters.
     */
    public function handle(array $query): array
    {
        if (! State::feedEnabled()) {
            return $this->fail(403, 'The catalogue feed is switched off for this store.');
        }

        // The token alone decides. Asyntai starts reading the catalogue the
        // moment the owner signs in, BEFORE this store has polled for its
        // site id, and refusing that first read would mark the store as
        // disconnected at Asyntai's end.
        $token = State::feedToken();

        if ($token === '') {
            return $this->fail(403, 'This store is not connected to Asyntai.');
        }

        $page = isset($query['page']) ? (int) $query['page'] : 1;
        $limit = isset($query['limit']) ? (int) $query['limit'] : self::PAGE_SIZE;
        $ids = isset($query['ids']) ? (string) $query['ids'] : '';
        $ts = isset($query['ts']) ? (string) $query['ts'] : '';
        $sig = isset($query['sig']) ? (string) $query['sig'] : '';

        if ($page < 1) {
            $page = 1;
        }

        if ($limit < 1 || $limit > self::MAX_PAGE_SIZE) {
            $limit = self::PAGE_SIZE;
        }

        // The signature covers the values AFTER they are read but BEFORE they
        // are clamped, so the string signed by Asyntai is the string checked
        // here.
        $signed = 'page=' . (isset($query['page']) ? (string) $query['page'] : '')
            . '&limit=' . (isset($query['limit']) ? (string) $query['limit'] : '')
            . '&ids=' . $ids
            . '&ts=' . $ts;

        if ($ts === '' || ! ctype_digit($ts)) {
            return $this->fail(403, 'Missing timestamp.');
        }

        // A replayed request is worth little here, but a signature with no
        // expiry is a credential that never dies. Five minutes is enough for
        // any clock a shop server is likely to keep.
        if (abs(time() - (int) $ts) > self::CLOCK_SKEW) {
            return $this->fail(403, 'The request has expired.');
        }

        $expected = hash_hmac('sha256', $signed, $token);

        if ($sig === '' || ! hash_equals($expected, $sig)) {
            return $this->fail(403, 'Bad signature.');
        }

        try {
            $this->channel = (string) core()->getDefaultChannelCode();
            $this->locale = (string) core()->getDefaultLocaleCodeFromDefaultChannel();
        } catch (\Throwable $e) {
            return $this->fail(500, 'The store has no default channel.');
        }

        if ($ids !== '') {
            return $this->byIds($ids);
        }

        return $this->byPage($page, $limit);
    }

    private function fail(int $status, string $message): array
    {
        return [
            'status' => $status,
            'body'   => ['ok' => false, 'error' => $message],
        ];
    }

    // -----------------------------------------------------------------
    // The two ways to ask
    // -----------------------------------------------------------------

    /**
     * Everything a shopper could land on, and nothing else.
     */
    private function visible()
    {
        return DB::table('product_flat')
            ->where('product_flat.channel', $this->channel)
            ->where('product_flat.locale', $this->locale)
            ->where('product_flat.status', 1)
            ->where('product_flat.visible_individually', 1);
    }

    /**
     * One page of the catalogue, in a stable order.
     *
     * Ordered by product id rather than by name, so a rename between two
     * pages cannot make a product appear twice or not at all.
     */
    private function byPage(int $page, int $limit): array
    {
        $total = (int) $this->visible()->count();

        $rows = $this->visible()
            ->orderBy('product_flat.product_id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get(['product_flat.product_id']);

        $products = [];

        foreach ($rows as $row) {
            $product = $this->one((int) $row->product_id);

            if ($product) {
                $products[] = $product;
            }
        }

        return [
            'status' => 200,
            'body'   => [
                'ok'       => true,
                'store'    => $this->storeInfo(),
                'page'     => $page,
                'pages'    => $limit > 0 ? (int) ceil($total / $limit) : 1,
                'total'    => $total,
                'products' => $products,
            ],
        ];
    }

    /**
     * Named products only.
     *
     * Asyntai calls this just before it answers a shopper, to make sure the
     * price and the stock it is about to quote are the ones the shop is
     * charging right now.
     */
    private function byIds(string $ids): array
    {
        $wanted = [];

        foreach (explode(',', $ids) as $raw) {
            $id = (int) trim($raw);

            if ($id > 0 && ! in_array($id, $wanted, true)) {
                $wanted[] = $id;
            }

            if (count($wanted) >= 50) {
                break;
            }
        }

        $products = [];

        foreach ($wanted as $id) {
            // Through the same visibility test as a page, so a product the
            // owner has since disabled is not quoted from a stale id.
            $visible = $this->visible()->where('product_flat.product_id', $id)->exists();

            if (! $visible) {
                continue;
            }

            $product = $this->one($id);

            if ($product) {
                $products[] = $product;
            }
        }

        return [
            'status' => 200,
            'body'   => [
                'ok'       => true,
                'store'    => $this->storeInfo(),
                'page'     => 1,
                'pages'    => 1,
                'total'    => count($products),
                'products' => $products,
            ],
        ];
    }

    // -----------------------------------------------------------------
    // Rendering one product
    // -----------------------------------------------------------------

    /**
     * @return array|null Null when the shop would not show this product.
     */
    private function one(int $productId): ?array
    {
        try {
            $product = $this->products->find($productId);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $product) {
            return null;
        }

        $flat = $this->visible()->where('product_flat.product_id', $productId)->first();

        if (! $flat || trim((string) $flat->name) === '') {
            return null;
        }

        $out = [
            'id'   => $productId,
            'name' => trim($this->decode((string) $flat->name)),
        ];

        $description = $this->plainText((string) ($flat->description ?? ''));

        if ($description === '') {
            $description = $this->plainText((string) ($flat->short_description ?? ''));
        }

        if ($description !== '') {
            $out['description'] = $description;
        }

        $sku = trim((string) ($product->sku ?? ''));

        if ($sku !== '') {
            $out['sku'] = $sku;
            // What OpenCart calls the model. Sent under both names so the
            // reader that already knows one shape needs no new branch.
            $out['model'] = $sku;
        }

        // Price. The type instance knows about specials, catalogue rules and
        // the children of a configurable product, and answers for the guest
        // group because this request carries no customer.
        [$regular, $final] = $this->prices($product, $flat);

        if ($final !== null && $regular !== null && $final < $regular) {
            $out['price'] = $this->money($regular);
            $out['special'] = $this->money($final);
        } elseif ($final !== null) {
            $out['price'] = $this->money($final);
        } elseif ($regular !== null) {
            $out['price'] = $this->money($regular);
        }

        $out['currency'] = $this->currency();

        $quantity = $this->quantity($product);

        if ($quantity !== null) {
            $out['quantity'] = $quantity;
        }

        $out['stock_status'] = $this->saleable($product) ? 'In Stock' : 'Out Of Stock';

        $categories = $this->categoryNames($product);

        if ($categories) {
            $out['categories'] = $categories;
        }

        $out['url'] = $this->productUrl((string) ($flat->url_key ?? ''));

        $image = $this->imageUrl($product);

        if ($image !== '') {
            $out['image_url'] = $image;
        }

        return $out;
    }

    /**
     * @return array{0: float|null, 1: float|null} Regular and final minimal price.
     */
    private function prices($product, $flat): array
    {
        $regular = null;
        $final = null;

        try {
            $type = $product->getTypeInstance();
            $regular = (float) $type->getRegularMinimalPrice();
            $final = (float) $type->getMinimalPrice();
        } catch (\Throwable $e) {
            // Fall through to the flat table, which always has a number.
        }

        if ($regular === null && isset($flat->price)) {
            $regular = (float) $flat->price;
        }

        if ($final === null) {
            $final = $regular;
        }

        return [$regular, $final];
    }

    private function quantity($product): ?int
    {
        try {
            return (int) $product->totalQuantity();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function saleable($product): bool
    {
        try {
            return (bool) $product->isSaleable();
        } catch (\Throwable $e) {
            return true;
        }
    }

    private function currency(): string
    {
        try {
            return (string) core()->getBaseCurrencyCode();
        } catch (\Throwable $e) {
            return (string) config('app.currency', '');
        }
    }

    /**
     * A price as a plain decimal string.
     *
     * Deliberately NOT run through the currency formatter. A formatted string
     * carries a symbol, a thousands separator and a session currency, none of
     * which survive being read back as a number.
     */
    private function money($value): string
    {
        return number_format((float) $value, 4, '.', '');
    }

    private function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** Description HTML reduced to the sentences a shopper would read. */
    private function plainText(string $html): string
    {
        $text = preg_replace('/<script[\s\S]*?<\/script>/i', ' ', $html);
        $text = preg_replace('/<style[\s\S]*?<\/style>/i', ' ', (string) $text);
        $text = preg_replace('/<[^>]+>/', ' ', (string) $text);
        $text = $this->decode((string) $text);

        // A non-breaking space is not matched by \s, so it is named here.
        $text = preg_replace('/[\s\x{00A0}]+/u', ' ', (string) $text);
        $text = trim((string) $text);

        if (mb_strlen($text, 'UTF-8') > self::MAX_DESCRIPTION) {
            $text = mb_substr($text, 0, self::MAX_DESCRIPTION, 'UTF-8');
        }

        return $text;
    }

    /** @return array<int, string> */
    private function categoryNames($product): array
    {
        $names = [];

        try {
            foreach ($product->categories as $category) {
                $name = trim((string) ($category->name ?? ''));

                if ($name !== '' && ! in_array($name, $names, true)) {
                    $names[] = $name;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $names;
    }

    /**
     * The address a shopper lands on: the store, then the product's url key.
     *
     * Built from the store address rather than from this request, so a feed
     * read over an internal hostname still hands out public links.
     */
    private function productUrl(string $urlKey): string
    {
        if ($urlKey === '') {
            return '';
        }

        return State::siteUrl() . '/' . ltrim($urlKey, '/');
    }

    /**
     * The medium cached image, absolute, or empty when the product has none.
     * Bagisto's own placeholder is left out on purpose: a picture of nothing
     * is worse than no picture in a result row.
     */
    private function imageUrl($product): string
    {
        try {
            if (! $product->images || ! $product->images->count()) {
                return '';
            }

            $image = product_image()->getProductBaseImage($product);

            return (string) ($image['medium_image_url'] ?? $image['large_image_url'] ?? '');
        } catch (\Throwable $e) {
            return '';
        }
    }

    // -----------------------------------------------------------------
    // The store itself
    // -----------------------------------------------------------------

    private function storeInfo(): array
    {
        $name = '';

        try {
            $channel = core()->getDefaultChannel();
            $name = $channel ? (string) $channel->name : '';
        } catch (\Throwable $e) {
            $name = '';
        }

        if ($name === '') {
            $name = (string) config('app.name', 'Bagisto');
        }

        $version = '';

        try {
            $version = (string) core()->version();
        } catch (\Throwable $e) {
            $version = '';
        }

        return [
            'name'     => $name,
            'url'      => State::siteUrl(),
            'currency' => $this->currency(),
            'platform' => 'bagisto',
            // Named, so that "2.3.8" on its own never has to be guessed at.
            'version'  => trim('Bagisto ' . $version),
        ];
    }
}
