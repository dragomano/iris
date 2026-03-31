<?php

declare(strict_types=1);

namespace Bugo\Iris\Operations;

use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;

final readonly class GamutMapper
{
    public function __construct(private SpaceConverter $converter = new SpaceConverter()) {}

    public function clip(OklchColor $oklch): OklchColor
    {
        $rgb = $this->converter->oklchToSrgbUnclamped($oklch);

        return $this->converter->normalizedSrgbToOklch(new RgbColor(
            r: $this->converter->clamp($rgb->r ?? 0.0, 1.0),
            g: $this->converter->clamp($rgb->g ?? 0.0, 1.0),
            b: $this->converter->clamp($rgb->b ?? 0.0, 1.0),
            a: $rgb->a
        ));
    }

    public function localMinde(OklchColor $oklch): OklchColor
    {
        $minChroma = 0.0;
        $maxChroma = $oklch->c ?? 0.0;

        $current = $this->converter->oklchToSrgbUnclamped($oklch);
        $clipped = $this->clipRgb($current);

        if ($this->converter->calculateDeltaE($current, $clipped) < 0.02) {
            return $this->converter->normalizedSrgbToOklch($clipped);
        }

        $bestClipped = null;

        while (($maxChroma - $minChroma) > 0.0001) {
            $testChroma = ($minChroma + $maxChroma) / 2.0;
            $testColor  = new OklchColor(
                l: $oklch->l,
                c: $testChroma,
                h: $oklch->h,
                a: $oklch->a
            );

            $current = $this->converter->oklchToSrgbUnclamped($testColor);
            $clipped = $this->clipRgb($current);

            $isInGamut = $current->r >= 0.0 && $current->r <= 1.0
                && $current->g >= 0.0 && $current->g <= 1.0
                && $current->b >= 0.0 && $current->b <= 1.0;

            $deltaE = $this->converter->calculateDeltaE($current, $clipped);

            if ($isInGamut || $deltaE < 0.02) {
                $minChroma   = $testChroma;
                $bestClipped = $clipped;

                continue;
            }

            $maxChroma = $testChroma;
        }

        return $this->converter->normalizedSrgbToOklch($bestClipped ?? $clipped);
    }

    private function clipRgb(RgbColor $rgb): RgbColor
    {
        return new RgbColor(
            r: $this->converter->clamp($rgb->r ?? 0.0, 1.0),
            g: $this->converter->clamp($rgb->g ?? 0.0, 1.0),
            b: $this->converter->clamp($rgb->b ?? 0.0, 1.0),
            a: $rgb->a
        );
    }
}
