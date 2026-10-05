<?php

use Asyntai\Search\Feed;

/**
 * Feed::prices() with a product type that answers whatever the test says.
 * Through reflection: the method is private, and the case under test (a
 * type with no regular price) is hard to make a real product produce.
 */
function feedPrices($regular, $final, $flatPrice): array
{
    $type = new class($regular, $final)
    {
        public function __construct(private $regular, private $final) {}

        public function getRegularMinimalPrice()
        {
            return $this->regular;
        }

        public function getMinimalPrice()
        {
            return $this->final;
        }
    };

    $product = new class($type)
    {
        public function __construct(private $type) {}

        public function getTypeInstance()
        {
            return $this->type;
        }
    };

    $flat = (object) ['price' => $flatPrice];

    $method = new ReflectionMethod(Feed::class, 'prices');

    $method->setAccessible(true);

    return $method->invoke(app(Feed::class), $product, $flat);
}

it('should fall back to the flat price when the type has no regular price', function () {
    // Act.
    [$regular, $final] = feedPrices(null, null, '12.5000');

    // Assert.
    expect($regular)->toBe(12.5)
        ->and($final)->toBe(12.5);
});

it('should never turn a missing price into 0', function () {
    // Act.
    [$regular, $final] = feedPrices(null, null, null);

    // Assert.
    expect($regular)->toBeNull()
        ->and($final)->toBeNull();
});

it('should keep the final price when only the regular price is missing', function () {
    // Act.
    [$regular, $final] = feedPrices(null, 9.99, '12.5000');

    // Assert.
    expect($regular)->toBe(12.5)
        ->and($final)->toBe(9.99);
});

it('should use the type prices when it has them', function () {
    // Act.
    [$regular, $final] = feedPrices(20, 15, '99.0000');

    // Assert.
    expect($regular)->toBe(20.0)
        ->and($final)->toBe(15.0);
});

it('should keep a real price of 0', function () {
    // Act.
    [$regular, $final] = feedPrices(0, 0, '12.5000');

    // Assert.
    expect($regular)->toBe(0.0)
        ->and($final)->toBe(0.0);
});

it('should fall back when the type throws', function () {
    // Arrange.
    $product = new class
    {
        public function getTypeInstance()
        {
            throw new RuntimeException('no type');
        }
    };

    $method = new ReflectionMethod(Feed::class, 'prices');

    $method->setAccessible(true);

    // Act.
    [$regular, $final] = $method->invoke(app(Feed::class), $product, (object) ['price' => '7.2500']);

    // Assert.
    expect($regular)->toBe(7.25)
        ->and($final)->toBe(7.25);
});
