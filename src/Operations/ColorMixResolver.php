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
use Closure;

use function fmod;

final readonly class ColorMixResolver
{
    public function __construct(private SpaceConverter $converter = new SpaceConverter()) {}

    public function mixSrgb(RgbColor $a, RgbColor $b, float $weight, bool $premultiplied = false): RgbColor
    {
        $alpha   = $this->converter->mixChannel($a->a, $b->a, $weight);
        $channel = $this->channelMixer($a->a, $b->a, $weight, $alpha, $premultiplied);

        return new RgbColor(
            r: $channel($a->r, $b->r),
            g: $channel($a->g, $b->g),
            b: $channel($a->b, $b->b),
            a: $alpha,
        );
    }

    public function mixHsl(
        HslColor $a,
        HslColor $b,
        float $weight,
        string $hueMethod = 'shorter',
        bool $premultiplied = false,
    ): HslColor {
        $alpha   = $this->converter->mixChannel($a->a, $b->a, $weight);
        $channel = $this->channelMixer($a->a, $b->a, $weight, $alpha, $premultiplied);

        return new HslColor(
            h: $this->hue($a->h, $b->h, $weight, $hueMethod),
            s: $channel($a->s, $b->s),
            l: $channel($a->l, $b->l),
            a: $alpha,
        );
    }

    public function mixOklab(OklabColor $a, OklabColor $b, float $weight, bool $premultiplied = false): OklabColor
    {
        $alpha   = $this->converter->mixChannel($a->alpha, $b->alpha, $weight);
        $channel = $this->channelMixer($a->alpha, $b->alpha, $weight, $alpha, $premultiplied);

        return new OklabColor(
            l: $channel($a->l, $b->l),
            a: $channel($a->a, $b->a),
            b: $channel($a->b, $b->b),
            alpha: $alpha,
        );
    }

    public function mixOklch(
        OklchColor $a,
        OklchColor $b,
        float $weight,
        string $hueMethod = 'shorter',
        bool $premultiplied = false,
    ): OklchColor {
        $alpha   = $this->converter->mixChannel($a->a, $b->a, $weight);
        $channel = $this->channelMixer($a->a, $b->a, $weight, $alpha, $premultiplied);

        return new OklchColor(
            l: $channel($a->l, $b->l),
            c: $channel($a->c, $b->c),
            h: $this->hue($a->h, $b->h, $weight, $hueMethod),
            a: $alpha,
        );
    }

    public function mixLab(LabColor $a, LabColor $b, float $weight, bool $premultiplied = false): LabColor
    {
        $alpha   = $this->converter->mixChannel($a->alpha, $b->alpha, $weight);
        $channel = $this->channelMixer($a->alpha, $b->alpha, $weight, $alpha, $premultiplied);

        return new LabColor(
            l: $channel($a->l, $b->l),
            a: $channel($a->a, $b->a),
            b: $channel($a->b, $b->b),
            alpha: $alpha,
        );
    }

    public function mixLch(
        LchColor $a,
        LchColor $b,
        float $weight,
        string $hueMethod = 'shorter',
        bool $premultiplied = false,
    ): LchColor {
        $alpha   = $this->converter->mixChannel($a->alpha, $b->alpha, $weight);
        $channel = $this->channelMixer($a->alpha, $b->alpha, $weight, $alpha, $premultiplied);

        return new LchColor(
            l: $channel($a->l, $b->l),
            c: $channel($a->c, $b->c),
            h: $this->hue($a->h, $b->h, $weight, $hueMethod),
            alpha: $alpha,
        );
    }

    /**
     * Builds a channel interpolator bound to the alpha pair of the two colors being mixed.
     *
     * @return Closure(?float, ?float): ?float
     */
    private function channelMixer(
        float $alphaA,
        float $alphaB,
        float $weight,
        float $resultAlpha,
        bool $premultiplied,
    ): Closure {
        if (! $premultiplied) {
            return fn(?float $a, ?float $b): ?float => $this->channel($a, $b, $weight);
        }

        return static function (?float $a, ?float $b) use ($alphaA, $alphaB, $weight, $resultAlpha): ?float {
            // CSS Color 4: missing components are carried forward before premultiplication
            if ($a === null || $b === null) {
                return $a ?? $b;
            }

            $mixed = ($a * $alphaA * $weight) + ($b * $alphaB * (1.0 - $weight));

            // CSS Color 4: a zero interpolated alpha leaves the premultiplied value as-is
            return $resultAlpha > 0.0 ? $mixed / $resultAlpha : $mixed;
        };
    }

    private function channel(?float $a, ?float $b, float $weight): ?float
    {
        if ($a === null || $b === null) {
            return $a ?? $b;
        }

        return $this->converter->mixChannel($a, $b, $weight);
    }

    private function hue(?float $h1, ?float $h2, float $weight, string $method): ?float
    {
        if ($h1 === null || $h2 === null) {
            return $h1 ?? $h2;
        }

        return $this->interpolateHueWithMethod($h1, $h2, $weight, $method);
    }

    private function interpolateHueWithMethod(float $h1, float $h2, float $p, string $method): float
    {
        if ($method === 'longer') {
            $delta = $h2 - $h1;

            if ($delta > 0.0 && $delta < 180.0) {
                $h2 += 360.0;
            }

            if ($delta > -180.0 && $delta <= 0.0) {
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

        return $h < 0.0 ? $h + 360.0 : $h;
    }
}
