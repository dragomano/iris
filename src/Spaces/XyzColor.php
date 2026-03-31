<?php

declare(strict_types=1);

namespace Bugo\Iris\Spaces;

use Bugo\Iris\Contracts\ColorValueInterface;

final readonly class XyzColor implements ColorValueInterface
{
    public function __construct(
        public float $x = 0.0,
        public float $y = 0.0,
        public float $z = 0.0,
        public float $alpha = 1.0
    ) {}

    public function getSpace(): string
    {
        return 'xyz-d65';
    }

    public function getChannels(): array
    {
        return [$this->x, $this->y, $this->z];
    }

    public function getAlpha(): float
    {
        return $this->alpha;
    }
}
