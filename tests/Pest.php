<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;

expect()->extend('toBeCloseTo', function (float $expected, float $delta = 0.01) {
    Assert::assertEqualsWithDelta($expected, $this->value, $delta);

    return $this;
});
