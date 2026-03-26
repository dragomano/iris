<?php

declare(strict_types=1);

namespace Bugo\Iris\Converters;

final readonly class NormalizedRgbChannels
{
    public function __construct(
        public float $r,
        public float $g,
        public float $b,
        public float $a,
        public float $max,
        public float $min,
        public float $delta
    ) {}
}
