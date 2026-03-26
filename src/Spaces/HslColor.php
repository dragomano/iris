<?php

declare(strict_types=1);

namespace Bugo\Iris\Spaces;

use Bugo\Iris\Contracts\ColorValueInterface;

final readonly class HslColor implements ColorValueInterface
{
    public function __construct(
        public float|null $h,
        public float|null $s,
        public float|null $l,
        public float $a = 1.0
    ) {}

    public function getSpace(): string
    {
        return 'hsl';
    }

    public function getChannels(): array
    {
        return [$this->h, $this->s, $this->l];
    }

    public function getAlpha(): float
    {
        return $this->a;
    }

    public function hValue(): float
    {
        return $this->h ?? 0.0;
    }

    public function sValue(): float
    {
        return $this->s ?? 0.0;
    }

    public function lValue(): float
    {
        return $this->l ?? 0.0;
    }
}
