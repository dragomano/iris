<?php

declare(strict_types=1);

namespace Bugo\Iris\Manipulators;

use Bugo\Iris\Converters\ModelConverter;
use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\RgbColor;

final readonly class LegacyManipulator
{
    public function __construct(
        private SpaceConverter $colorSpaceConverter = new SpaceConverter(),
        private ModelConverter $colorModelConverter = new ModelConverter()
    ) {}

    public function grayscale(HslColor $color): HslColor
    {
        return new HslColor(h: $color->h, s: 0.0, l: $color->l, a: $color->a);
    }

    public function mix(RgbColor $left, RgbColor $right, float $weight): RgbColor
    {
        return new RgbColor(
            r: $this->colorSpaceConverter->mixChannel($left->r ?? 0.0, $right->r ?? 0.0, $weight),
            g: $this->colorSpaceConverter->mixChannel($left->g ?? 0.0, $right->g ?? 0.0, $weight),
            b: $this->colorSpaceConverter->mixChannel($left->b ?? 0.0, $right->b ?? 0.0, $weight),
            a: $this->colorSpaceConverter->mixChannel($left->a, $right->a, $weight)
        );
    }

    public function invert(RgbColor $color, float $weight): RgbColor
    {
        $r = $color->r ?? 0.0;
        $g = $color->g ?? 0.0;
        $b = $color->b ?? 0.0;

        return new RgbColor(
            r: $this->colorSpaceConverter->mixChannel($r, 255.0 - $r, 1.0 - $weight),
            g: $this->colorSpaceConverter->mixChannel($g, 255.0 - $g, 1.0 - $weight),
            b: $this->colorSpaceConverter->mixChannel($b, 255.0 - $b, 1.0 - $weight),
            a: $color->a
        );
    }

    /**
     * @param array<string, float|null> $scales
     */
    public function scale(RgbColor $rgb, HslColor $hsl, array $scales): RgbColor
    {
        $newRed        = $this->scaleChannel($rgb->r ?? 0.0, $scales['red'] ?? null, 255.0);
        $newGreen      = $this->scaleChannel($rgb->g ?? 0.0, $scales['green'] ?? null, 255.0);
        $newBlue       = $this->scaleChannel($rgb->b ?? 0.0, $scales['blue'] ?? null, 255.0);
        $newAlpha      = $this->scaleChannel($rgb->a, $scales['alpha'] ?? null, 1.0);
        $newSaturation = $this->scaleChannel($hsl->s ?? 0.0, $scales['saturation'] ?? null, 100.0);
        $newLightness  = $this->scaleChannel($hsl->l ?? 0.0, $scales['lightness'] ?? null, 100.0);

        if (isset($scales['saturation']) || isset($scales['lightness'])) {
            $changed = $this->colorModelConverter->hslToRgbColor(new HslColor(
                h: $hsl->h,
                s: $newSaturation,
                l: $newLightness,
                a: $hsl->a
            ));

            $newRed   = $changed->r;
            $newGreen = $changed->g;
            $newBlue  = $changed->b;
        }

        return new RgbColor(
            r: $newRed,
            g: $newGreen,
            b: $newBlue,
            a: $newAlpha
        );
    }

    /**
     * @param array<string, float|null> $adjustments
     */
    public function adjust(RgbColor $rgb, HslColor $hsl, array $adjustments): RgbColor
    {
        return $this->modify(
            $rgb,
            $hsl,
            $adjustments,
            static fn(float $current, float $value): float => $current + $value
        );
    }

    /**
     * @param array<string, float|null> $changes
     */
    public function change(RgbColor $rgb, HslColor $hsl, array $changes): RgbColor
    {
        return $this->modify($rgb, $hsl, $changes, static fn(float $current, float $value): float => $value);
    }

    public function adjustHue(RgbColor $rgb, HslColor $hsl, float $degrees): RgbColor
    {
        return $this->adjust($rgb, $hsl, ['hue' => $degrees]);
    }

    public function adjustAlpha(RgbColor $rgb, HslColor $hsl, float $amount): RgbColor
    {
        return $this->adjust($rgb, $hsl, ['alpha' => $amount]);
    }

    public function adjustLightness(RgbColor $rgb, HslColor $hsl, float $amount): RgbColor
    {
        return $this->adjust($rgb, $hsl, ['lightness' => $amount]);
    }

    public function adjustSaturation(RgbColor $rgb, HslColor $hsl, float $amount): RgbColor
    {
        return $this->adjust($rgb, $hsl, ['saturation' => $amount]);
    }

    public function darken(RgbColor $rgb, float $amount): RgbColor
    {
        $hsl = $this->colorModelConverter->rgbToHslColor($rgb);

        return $this->adjustLightness($rgb, $hsl, -$amount);
    }

    public function lighten(RgbColor $rgb, float $amount): RgbColor
    {
        $hsl = $this->colorModelConverter->rgbToHslColor($rgb);

        return $this->adjustLightness($rgb, $hsl, $amount);
    }

    public function saturate(RgbColor $rgb, float $amount): RgbColor
    {
        $hsl = $this->colorModelConverter->rgbToHslColor($rgb);

        return $this->adjustSaturation($rgb, $hsl, $amount);
    }

    public function desaturate(RgbColor $rgb, float $amount): RgbColor
    {
        $hsl = $this->colorModelConverter->rgbToHslColor($rgb);

        return $this->adjustSaturation($rgb, $hsl, -$amount);
    }

    public function fadeIn(RgbColor $rgb, float $amount): RgbColor
    {
        $hsl = $this->colorModelConverter->rgbToHslColor($rgb);

        return $this->adjustAlpha($rgb, $hsl, $amount);
    }

    public function fadeOut(RgbColor $rgb, float $amount): RgbColor
    {
        $hsl = $this->colorModelConverter->rgbToHslColor($rgb);

        return $this->adjustAlpha($rgb, $hsl, -$amount);
    }

    public function spin(RgbColor $rgb, float $degrees): RgbColor
    {
        $hsl = $this->colorModelConverter->rgbToHslColor($rgb);

        return $this->adjustHue($rgb, $hsl, $degrees);
    }

    private function scaleChannel(float $current, ?float $amount, float $max): float
    {
        if ($amount === null) {
            return $current;
        }

        return $this->colorSpaceConverter->scaleLinear($current, $amount, $max);
    }

    /**
     * @param array<string, float|null> $values
     * @param callable(float, float): float $modify
     */
    private function modify(RgbColor $rgb, HslColor $hsl, array $values, callable $modify): RgbColor
    {
        $newRed        = $this->modifyNumberChannel($rgb->r ?? 0.0, $values['red'] ?? null, $modify, 255.0);
        $newGreen      = $this->modifyNumberChannel($rgb->g ?? 0.0, $values['green'] ?? null, $modify, 255.0);
        $newBlue       = $this->modifyNumberChannel($rgb->b ?? 0.0, $values['blue'] ?? null, $modify, 255.0);
        $newAlpha      = $this->modifyNumberChannel($rgb->a, $values['alpha'] ?? null, $modify, 1.0);
        $newHue        = $this->modifyHueChannel($hsl->h ?? 0.0, $values['hue'] ?? null, $modify);
        $newSaturation = $this->modifyPercentageChannel($hsl->s ?? 0.0, $values['saturation'] ?? null, $modify);
        $newLightness  = $this->modifyPercentageChannel($hsl->l ?? 0.0, $values['lightness'] ?? null, $modify);

        if (isset($values['hue']) || isset($values['saturation']) || isset($values['lightness'])) {
            $changed = $this->colorModelConverter->hslToRgbColor(new HslColor(
                h: $newHue,
                s: $newSaturation,
                l: $newLightness,
                a: $hsl->a
            ));

            $newRed   = $changed->r;
            $newGreen = $changed->g;
            $newBlue  = $changed->b;
        }

        return new RgbColor(
            r: $newRed,
            g: $newGreen,
            b: $newBlue,
            a: $newAlpha
        );
    }

    /**
     * @param callable(float, float): float $modify
     */
    private function modifyNumberChannel(float $current, ?float $value, callable $modify, float $max): float
    {
        if ($value === null) {
            return $current;
        }

        return $this->colorSpaceConverter->clamp($modify($current, $value), $max);
    }

    /**
     * @param callable(float, float): float $modify
     */
    private function modifyPercentageChannel(float $current, ?float $value, callable $modify): float
    {
        if ($value === null) {
            return $current;
        }

        return $this->colorSpaceConverter->clamp($modify($current, $value), 100.0);
    }

    /**
     * @param callable(float, float): float $modify
     */
    private function modifyHueChannel(float $current, ?float $value, callable $modify): float
    {
        if ($value === null) {
            return $current;
        }

        return $this->colorSpaceConverter->normalizeHue($modify($current, $value));
    }
}
