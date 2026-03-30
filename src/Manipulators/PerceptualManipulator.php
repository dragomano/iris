<?php

declare(strict_types=1);

namespace Bugo\Iris\Manipulators;

use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\OklchColor;

use function max;

final readonly class PerceptualManipulator
{
    public function __construct(
        private SpaceConverter $colorSpaceConverter = new SpaceConverter()
    ) {}

    /**
     * @param array<string, float|null> $values
     */
    public function adjustOklch(OklchColor $color, array $values): OklchColor
    {
        return $this->modifyOklch(
            $color,
            $values,
            static fn(float $current, float $value): float => $current + $value
        );
    }

    /**
     * @param array<string, float|null> $values
     */
    public function changeOklch(OklchColor $color, array $values): OklchColor
    {
        return $this->modifyOklch(
            $color,
            $values,
            static fn(float $current, float $value): float => $value
        );
    }

    /**
     * @param array<string, float|null> $values
     */
    public function adjustLab(LabColor $color, array $values): LabColor
    {
        return $this->modifyLab(
            $color,
            $values,
            static fn(float $current, float $value): float => $current + $value
        );
    }

    /**
     * @param array<string, float|null> $values
     */
    public function changeLab(LabColor $color, array $values): LabColor
    {
        return $this->modifyLab(
            $color,
            $values,
            static fn(float $current, float $value): float => $value
        );
    }

    /**
     * @param array<string, float|null> $values
     * @param callable(float, float): float $modify
     */
    private function modifyOklch(OklchColor $color, array $values, callable $modify): OklchColor
    {
        return new OklchColor(
            l: $this->modifyPercentage($color->l ?? 0.0, $values['lightness'] ?? null, $modify),
            c: $this->modifyNonNegativeNumber($color->c ?? 0.0, $values['chroma'] ?? null, $modify),
            h: $this->modifyHue($color->h ?? 0.0, $values['hue'] ?? null, $modify),
            a: $this->modifyNumber($color->a, $values['alpha'] ?? null, $modify)
        );
    }

    /**
     * @param array<string, float|null> $values
     * @param callable(float, float): float $modify
     */
    private function modifyLab(LabColor $color, array $values, callable $modify): LabColor
    {
        return new LabColor(
            l: $this->modifyPercentage($color->l ?? 0.0, $values['lightness'] ?? null, $modify),
            a: $this->modifyUnboundedNumber($color->a ?? 0.0, $values['a'] ?? null, $modify),
            b: $this->modifyUnboundedNumber($color->b ?? 0.0, $values['b'] ?? null, $modify),
            alpha: $this->modifyNumber($color->alpha, $values['alpha'] ?? null, $modify)
        );
    }

    /**
     * @param callable(float, float): float $modify
     */
    private function modifyNumber(float $current, ?float $value, callable $modify): float
    {
        return $value === null
            ? $current
            : $this->colorSpaceConverter->clamp($modify($current, $value), 1.0);
    }

    /**
     * @param callable(float, float): float $modify
     */
    private function modifyPercentage(float $current, ?float $value, callable $modify): float
    {
        if ($value === null) {
            return $current;
        }

        return $this->colorSpaceConverter->clamp($modify($current, $value), 100.0);
    }

    /**
     * @param callable(float, float): float $modify
     */
    private function modifyHue(float $current, ?float $value, callable $modify): float
    {
        if ($value === null) {
            return $current;
        }

        return $this->colorSpaceConverter->normalizeHue($modify($current, $value));
    }

    /**
     * @param callable(float, float): float $modify
     */
    private function modifyNonNegativeNumber(float $current, ?float $value, callable $modify): float
    {
        if ($value === null) {
            return $current;
        }

        return max(0.0, $modify($current, $value));
    }

    /**
     * @param callable(float, float): float $modify
     */
    private function modifyUnboundedNumber(float $current, ?float $value, callable $modify): float
    {
        if ($value === null) {
            return $current;
        }

        return $modify($current, $value);
    }
}
