# Asyntai AI Search for Bagisto

A search bar for your Bagisto store that understands what shoppers mean. Somebody types "something warm for cold mornings" and gets your fleece jackets, with the price and the stock your store shows right now.

Results come from your own catalogue and your pages. Prices, stock and product links are read straight from your store, so they are never out of date.

## What you get

* **Understands the question.** Shoppers describe what they want in their own words, in any language, and the bar finds it. No exact product names needed.
* **Live prices and stock.** The bar reads your catalogue through a signed, read-only feed the package adds to your store. A price change shows in search the moment it happens.
* **Products, categories and pages in one box.** Shipping terms, size guides and return policies are found alongside the products.
* **Takes the place of your search box.** One click in the admin and the bar sits where your theme's search box was. Pressing Enter without picking a result still opens your usual search page.
* **Never leaves your store without search.** If your allowance runs out or the service is unreachable, the theme's own search box is back in place.

## Requirements

* Bagisto 2.1 or newer
* PHP 8.1 or newer
* Outgoing HTTPS from the server that runs your store

## Installation

### With Composer

```bash
composer require asyntai/bagisto-ai-search
php artisan migrate
php artisan optimize:clear
```

Laravel picks the service provider up on its own.

### By hand

1. Copy this package to `packages/Asyntai/Search` in your Bagisto project.
2. Add the namespace to `composer.json`, under `autoload.psr-4`:

   ```json
   "Asyntai\\Search\\": "packages/Asyntai/Search/src"
   ```

3. Register the service provider in `bootstrap/providers.php`:

   ```php
   Asyntai\Search\Providers\SearchServiceProvider::class,
   ```

4. Run:

   ```bash
   composer dump-autoload
   php artisan migrate
   php artisan optimize:clear
   ```

## Connecting your store

1. In the admin, open **AI Search** in the left menu.
2. Press **Connect Asyntai** and sign in, or create a free account, in the window that opens.
3. That is all. Asyntai reads your catalogue and your pages, and the bar switches on by itself when they are ready, usually within a few minutes. The AI Search page shows where it is.

## Settings

* **Where the bar goes.** In place of your theme's search box (the default), or only where your theme prints `<div data-asyntai-search></div>`.
* **Search box to replace.** Leave empty and the package finds it. Fill in a CSS selector only if it picks the wrong one.
* **Placeholder text** and **accent colour.** Leave empty to use what is set in your Asyntai dashboard.
* **Share my catalogue with Asyntai.** On by default. Switch it off and the bar searches your pages only.

## The catalogue feed

The package answers on `/asyntai-search/feed`. Every request must carry a fresh timestamp and an HMAC-SHA256 signature made with a token that only your store and Asyntai hold, so nobody else can read it. It lists the products a shopper can see, with the prices a shopper would pay, and nothing else: there is no route to orders, customers or settings.

## Test servers

Two environment variables point the package at another server. They are for staging copies and for Asyntai's own tests; a live store never needs them.

```
ASYNTAI_SEARCH_ORIGIN=https://staging.example.com
ASYNTAI_SEARCH_SCRIPT=https://staging.example.com/static/js/search-widget.js
```

## Documentation and support

Full guide: https://asyntai.com/documentation/bagisto-ai-search/

Email us at hello@asyntai.com.

## License

MIT. See LICENSE.
