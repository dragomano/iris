<?php

declare(strict_types=1);

namespace Bugo\Iris\Spaces;

use Bugo\Iris\Contracts\ColorValueInterface;

final readonly class OklabColor implements ColorValueInterface
{
    public function __construct(
        public float|null $l,
        public float|null $a,
        public float|null $b,
        public float $alpha = 1.0
    ) {}

    public function getSpace(): string
    {
        return 'oklab';
    }

    public function getChannels(): array
    {
        return [$this->l, $this->a, $this->b];
    }

    public function getAlpha(): float
    {
        return $this->alpha;
    }

    public function lValue(): float
    {
        return $this->l ?? 0.0;
    }

    public function aValue(): float
    {
        return $this->a ?? 0.0;
    }

    public function bValue(): float
    {
        return $this->b ?? 0.0;
    }
}
