<?php

declare(strict_types=1);

namespace Bugo\Iris\Manipulators;

use Bugo\Iris\Converters\SpaceConverter;

final readonly class SrgbManipulator
{
    public function __construct(private SpaceConverter $colorSpaceConverter = new SpaceConverter()) {}

    /**
     * @param array<string, float|null> $values
     * @return array{0: float, 1: float, 2: float}
     */
    public function adjust(float $red, float $green, float $blue, array $values): array
    {
        return $this->modify(
            $red,
            $green,
            $blue,
            $values,
            static fn(float $current, float $value): float => $current + $value,
        );
    }

    /**
     * @param array<string, float|null> $values
     * @return array{0: float, 1: float, 2: float}
     */
    public function change(float $red, float $green, float $blue, array $values): array
    {
        return $this->modify(
            $red,
            $green,
            $blue,
            $values,
            static fn(float $current, float $value): float => $value,
        );
    }

    /**
     * @param array<string, float|null> $values
     * @param callable(float, float): float $modify
     * @return array{0: float, 1: float, 2: float}
     */
    private function modify(float $red, float $green, float $blue, array $values, callable $modify): array
    {
        return [
            $this->modifyChannel($red, $values['red'] ?? null, $modify),
            $this->modifyChannel($green, $values['green'] ?? null, $modify),
            $this->modifyChannel($blue, $values['blue'] ?? null, $modify),
        ];
    }

    /**
     * @param callable(float, float): float $modify
     */
    private function modifyChannel(float $current, ?float $value, callable $modify): float
    {
        if ($value === null) {
            return $current;
        }

        return $this->colorSpaceConverter->clamp($modify($current, $value), 1.0);
    }
}
