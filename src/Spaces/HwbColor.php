<?php

declare(strict_types=1);

namespace Bugo\Iris\Spaces;

use Bugo\Iris\Contracts\ColorValueInterface;

final readonly class HwbColor implements ColorValueInterface
{
    public function __construct(
        public float|null $h,
        public float|null $w,
        public float|null $b,
        public float $a = 1.0
    ) {}

    public function getSpace(): string
    {
        return 'hwb';
    }

    public function getChannels(): array
    {
        return [$this->h, $this->w, $this->b];
    }

    public function getAlpha(): float
    {
        return $this->a;
    }

    public function hValue(): float
    {
        return $this->h ?? 0.0;
    }

    public function wValue(): float
    {
        return $this->w ?? 0.0;
    }

    public function bValue(): float
    {
        return $this->b ?? 0.0;
    }
}
