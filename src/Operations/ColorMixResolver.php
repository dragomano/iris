<?php

declare(strict_types=1);

namespace Bugo\Iris\Operations;

use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\LchColor;
use Bugo\Iris\Spaces\OklabColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;

use function fmod;

final readonly class ColorMixResolver
{
    public function __construct(private SpaceConverter $converter = new SpaceConverter()) {}

    public function mixSrgb(RgbColor $a, RgbColor $b, float $weight, bool $premultiplied = false): RgbColor
    {
        if ($premultiplied) {
            return $this->mixSrgbPremultiplied($a, $b, $weight);
        }

        return new RgbColor(
            r: $this->channel($a->r, $b->r, $weight) ?? 0.0,
            g: $this->channel($a->g, $b->g, $weight) ?? 0.0,
            b: $this->channel($a->b, $b->b, $weight) ?? 0.0,
            a: $this->converter->mixChannel($a->a, $b->a, $weight)
        );
    }

    public function mixHsl(HslColor $a, HslColor $b, float $weight, string $hueMethod = 'shorter'): HslColor
    {
        return new HslColor(
            h: $this->hue($a->h, $b->h, $weight, $hueMethod),
            s: $this->channel($a->s, $b->s, $weight),
            l: $this->channel($a->l, $b->l, $weight),
            a: $this->converter->mixChannel($a->a, $b->a, $weight)
        );
    }

    public function mixOklab(OklabColor $a, OklabColor $b, float $weight): OklabColor
    {
        return new OklabColor(
            l: $this->channel($a->l, $b->l, $weight),
            a: $this->channel($a->a, $b->a, $weight),
            b: $this->channel($a->b, $b->b, $weight),
            alpha: $this->converter->mixChannel($a->alpha, $b->alpha, $weight)
        );
    }

    public function mixOklch(OklchColor $a, OklchColor $b, float $weight, string $hueMethod = 'shorter'): OklchColor
    {
        return new OklchColor(
            l: $this->channel($a->l, $b->l, $weight),
            c: $this->channel($a->c, $b->c, $weight),
            h: $this->hue($a->h, $b->h, $weight, $hueMethod),
            a: $this->converter->mixChannel($a->a, $b->a, $weight)
        );
    }

    public function mixLab(LabColor $a, LabColor $b, float $weight): LabColor
    {
        return new LabColor(
            l: $this->channel($a->l, $b->l, $weight),
            a: $this->channel($a->a, $b->a, $weight),
            b: $this->channel($a->b, $b->b, $weight),
            alpha: $this->converter->mixChannel($a->alpha, $b->alpha, $weight)
        );
    }

    public function mixLch(LchColor $a, LchColor $b, float $weight, string $hueMethod = 'shorter'): LchColor
    {
        return new LchColor(
            l: $this->channel($a->l, $b->l, $weight),
            c: $this->channel($a->c, $b->c, $weight),
            h: $this->hue($a->h, $b->h, $weight, $hueMethod)
        );
    }

    private function channel(?float $a, ?float $b, float $weight): ?float
    {
        if ($a === null && $b === null) {
            return null;
        }

        if ($a === null) {
            return $b;
        }

        if ($b === null) {
            return $a;
        }

        return $this->converter->mixChannel($a, $b, $weight);
    }

    private function hue(?float $h1, ?float $h2, float $weight, string $method): ?float
    {
        if ($h1 === null && $h2 === null) {
            return null;
        }

        if ($h1 === null) {
            return $h2;
        }

        if ($h2 === null) {
            return $h1;
        }

        return $this->interpolateHueWithMethod($h1, $h2, $weight, $method);
    }

    private function interpolateHueWithMethod(float $h1, float $h2, float $p, string $method): float
    {
        if ($method === 'longer') {
            $delta = $h2 - $h1;

            if ($delta > 0.0 && $delta < 180.0) {
                $h2 += 360.0;
            } elseif ($delta > -180.0 && $delta <= 0.0) {
                $h1 += 360.0;
            }

            return $this->normalizeHue($this->converter->mixChannel($h1, $h2, $p));
        }

        if ($method === 'increasing') {
            if ($h2 < $h1) {
                $h2 += 360.0;
            }

            return $this->normalizeHue($this->converter->mixChannel($h1, $h2, $p));
        }

        if ($method === 'decreasing') {
            if ($h1 < $h2) {
                $h1 += 360.0;
            }

            return $this->normalizeHue($this->converter->mixChannel($h1, $h2, $p));
        }

        return $this->converter->interpolateHue($h1, $h2, $p);
    }

    private function normalizeHue(float $hue): float
    {
        $h = fmod($hue, 360.0);

        if ($h < 0.0) {
            $h += 360.0;
        }

        return $h;
    }

    private function mixSrgbPremultiplied(RgbColor $a, RgbColor $b, float $weight): RgbColor
    {
        $resultAlpha = $this->converter->mixChannel($a->a, $b->a, $weight);

        if ($resultAlpha <= 0.0) {
            return new RgbColor(r: 0.0, g: 0.0, b: 0.0, a: 0.0);
        }

        $rPremult = ($a->r ?? 0.0) * $a->a;
        $gPremult = ($a->g ?? 0.0) * $a->a;
        $bPremult = ($a->b ?? 0.0) * $a->a;

        $rPremult2 = ($b->r ?? 0.0) * $b->a;
        $gPremult2 = ($b->g ?? 0.0) * $b->a;
        $bPremult2 = ($b->b ?? 0.0) * $b->a;

        $rMixed = $rPremult * $weight + $rPremult2 * (1.0 - $weight);
        $gMixed = $gPremult * $weight + $gPremult2 * (1.0 - $weight);
        $bMixed = $bPremult * $weight + $bPremult2 * (1.0 - $weight);

        return new RgbColor(
            r: $rMixed / $resultAlpha,
            g: $gMixed / $resultAlpha,
            b: $bMixed / $resultAlpha,
            a: $resultAlpha
        );
    }
}
