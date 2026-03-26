<?php

declare(strict_types=1);

namespace Bugo\Iris\Spaces;

use Bugo\Iris\Contracts\ColorValueInterface;

final readonly class RgbColor implements ColorValueInterface
{
    public function __construct(
        public float|null $r = 0.0,
        public float|null $g = 0.0,
        public float|null $b = 0.0,
        public float $a = 1.0
    ) {}

    public function getSpace(): string
    {
        return 'rgb';
    }

    public function getChannels(): array
    {
        return [$this->r, $this->g, $this->b];
    }

    public function getAlpha(): float
    {
        return $this->a;
    }

    public function rValue(): float
    {
        return $this->r ?? 0.0;
    }

    public function gValue(): float
    {
        return $this->g ?? 0.0;
    }

    public function bValue(): float
    {
        return $this->b ?? 0.0;
    }
}
