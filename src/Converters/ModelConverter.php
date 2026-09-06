<?php

declare(strict_types=1);

namespace Bugo\Iris\Converters;

use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\RgbColor;

use function abs;
use function max;
use function min;
use function round;

final readonly class ModelConverter
{
    public function __construct(
        private SpaceConverter $colorSpaceConverter = new SpaceConverter(),
    ) {}

    public function rgbToHslColor(RgbColor $rgb): HslColor
    {
        $r = $rgb->r ?? 0.0;
        $g = $rgb->g ?? 0.0;
        $b = $rgb->b ?? 0.0;

        $max   = max($r, $g, $b);
        $min   = min($r, $g, $b);
        $delta = $max - $min;

        $channels = new NormalizedRgbChannels($r, $g, $b, $rgb->a, $max, $min, $delta);

        $h = $this->colorSpaceConverter->hueFromNormalizedRgb($channels);
        $l = ($max + $min) / 2.0;
        $s = $delta > 0.0 ? $delta / (1.0 - abs((2.0 * $l) - 1.0)) : 0.0;

        return new HslColor(
            h: $h,
            s: $this->roundFloat($s * 100.0),
            l: $this->roundFloat($l * 100.0),
            a: $rgb->a,
        );
    }

    public function hslToRgbColor(HslColor $hsl): RgbColor
    {
        [$r, $g, $b] = $this->colorSpaceConverter->hslToRgb(
            $this->colorSpaceConverter->normalizeHue($hsl->h ?? 0.0),
            $this->colorSpaceConverter->clamp($hsl->s ?? 0.0, 100.0) / 100.0,
            $this->colorSpaceConverter->clamp($hsl->l ?? 0.0, 100.0) / 100.0,
        );

        return new RgbColor(
            r: $this->normalizeRgbChannel($r),
            g: $this->normalizeRgbChannel($g),
            b: $this->normalizeRgbChannel($b),
            a: $this->colorSpaceConverter->clamp($hsl->a, 1.0),
        );
    }

    private function roundFloat(float $value, int $precision = 6): float
    {
        return round($value, $precision);
    }

    private function normalizeRgbChannel(float $value): float
    {
        $scaled  = $value * 255.0;
        $rounded = round($scaled);

        if (abs($scaled - $rounded) < 0.00001) {
            return $rounded / 255.0;
        }

        return $value;
    }
}
