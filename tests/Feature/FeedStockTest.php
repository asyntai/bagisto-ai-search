<?php

use Webkul\Faker\Helpers\Product as ProductFaker;

beforeEach(function () {
    $this->connectStore();
});

it('should send the quantity of a simple product', function () {
    // Arrange.
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    // Act.
    $sent = $this->feedProduct($product->id);

    // Assert.
    expect($sent)->toHaveKey('quantity')
        ->and($sent['quantity'])->toBe((int) $product->totalQuantity());
});

it('should leave out the quantity of a grouped product, which holds no stock of its own', function () {
    // Arrange.
    $product = (new ProductFaker)->getGroupedProductFactory()->create();

    // Act.
    $sent = $this->feedProduct($product->id);

    // Assert.
    expect($sent)->not->toBeNull()
        ->and($sent)->not->toHaveKey('quantity')
        ->and($sent['stock_status'])->toBe($product->isSaleable() ? 'In Stock' : 'Out Of Stock');
});

it('should leave out the quantity of a bundle product, which holds no stock of its own', function () {
    // Arrange.
    $product = (new ProductFaker)->getBundleProductFactory()->create();

    // Act.
    $sent = $this->feedProduct($product->id);

    // Assert.
    expect($sent)->not->toBeNull()
        ->and($sent)->not->toHaveKey('quantity');
});

it('should never send a quantity of 0 for a product in stock', function () {
    // Arrange.
    (new ProductFaker)->getGroupedProductFactory()->create();

    (new ProductFaker)->getBundleProductFactory()->create();

    // Act.
    $products = $this->getFeed(['page' => '1', 'limit' => '250'])->assertOk()->json('products');

    // Assert.
    foreach ($products as $product) {
        if (($product['stock_status'] ?? '') === 'In Stock' && array_key_exists('quantity', $product)) {
            expect($product['quantity'])->toBeGreaterThan(0);
        }
    }
});
