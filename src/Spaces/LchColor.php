<?php

declare(strict_types=1);

namespace Bugo\Iris\Spaces;

use Bugo\Iris\Contracts\ColorValueInterface;

final readonly class LchColor implements ColorValueInterface
{
    public function __construct(
        public float|null $l,
        public float|null $c,
        public float|null $h,
        public float $alpha = 1.0
    ) {}

    public function getSpace(): string
    {
        return 'lch';
    }

    public function getChannels(): array
    {
        return [$this->l, $this->c, $this->h];
    }

    public function getAlpha(): float
    {
        return $this->alpha;
    }

    public function lValue(): float
    {
        return $this->l ?? 0.0;
    }

    public function cValue(): float
    {
        return $this->c ?? 0.0;
    }

    public function hValue(): float
    {
        return $this->h ?? 0.0;
    }
}
