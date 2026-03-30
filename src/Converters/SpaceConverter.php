<?php

declare(strict_types=1);

namespace Bugo\Iris\Converters;

use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\LchColor;
use Bugo\Iris\Spaces\OklabColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;
use Bugo\Iris\Spaces\XyzColor;

use function abs;
use function atan2;
use function cos;
use function fmod;
use function max;
use function min;
use function round;
use function rtrim;
use function sin;
use function sprintf;
use function sqrt;

use const M_PI;

final readonly class SpaceConverter
{
    // @pest-mutate-ignore
    private const LAB_EPSILON = 0.008856451679035631; // 216 / 24389

    // @pest-mutate-ignore
    private const LAB_KAPPA = 903.2962962962963; // 24389 / 27

    // @pest-mutate-ignore
    private const LAB_DELTA = 16.0;

    // @pest-mutate-ignore
    private const LAB_SCALE = 116.0;

    // @pest-mutate-ignore
    private const LAB_A_FACTOR = 500.0;

    // @pest-mutate-ignore
    private const LAB_B_FACTOR = 200.0;

    // @pest-mutate-ignore
    private const D50_WHITE_X = 0.9642956764295677;

    // @pest-mutate-ignore
    private const D50_WHITE_Y = 1.0;

    // @pest-mutate-ignore
    private const D50_WHITE_Z = 0.8251046025104602;

    public function clamp(float|null $value, float $max): float
    {
        return max(0.0, min($max, $value ?? 0.0));
    }

    public function normalizeHue(float $hue): float
    {
        $h = fmod($hue, 360.0);

        if ($h < 0.0) {
            $h += 360.0;
        }

        return $h;
    }

    public function roundFloat(float $value, int $precision = 6): float
    {
        return round($value, $precision);
    }

    public function srgbToLinear(float $value): float
    {
        $value = $this->clamp($value, 1.0);

        if ($value <= 0.04045) {
            return $value / 12.92;
        }

        return (($value + 0.055) / 1.055) ** 2.4;
    }

    public function srgbToLinearUnclamped(float|null $value): float
    {
        $value ??= 0.0;

        if ($value <= 0.04045) {
            return $value / 12.92;
        }

        return (($value + 0.055) / 1.055) ** 2.4;
    }

    public function linearToSrgb(float $value): float
    {
        $value = $this->clamp($value, 1.0);

        if ($value <= 0.0031308) {
            return 12.92 * $value;
        }

        return 1.055 * $value ** (1.0 / 2.4) - 0.055;
    }

    public function linearToSrgbUnclamped(float $value): float
    {
        if ($value <= 0.0031308) {
            return 12.92 * $value;
        }

        return 1.055 * $value ** (1.0 / 2.4) - 0.055;
    }

    public function cubeRoot(float $value): float
    {
        if ($value === 0.0) {
            return 0.0;
        }

        return $value > 0.0 ? $value ** (1.0 / 3.0) : -abs($value) ** (1.0 / 3.0);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function hslToRgb(float $hueDegrees, float $saturation, float $lightness): array
    {
        $hue = $hueDegrees / 360.0;

        if ($saturation <= 0.0) {
            $channel = $this->clamp($lightness, 1.0);

            return [$channel, $channel, $channel];
        }

        $q = $lightness < 0.5
            ? $lightness * (1.0 + $saturation)
            : $lightness + $saturation - ($lightness * $saturation);
        $p = 2.0 * $lightness - $q;

        return [
            $this->hueToRgb($p, $q, $hue + (1.0 / 3.0)),
            $this->hueToRgb($p, $q, $hue),
            $this->hueToRgb($p, $q, $hue - (1.0 / 3.0)),
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function hwbToRgb(float $hueDegrees, float $whiteness, float $blackness): array
    {
        $sum = $whiteness + $blackness;

        if ($sum >= 1.0) {
            $gray = $sum === 0.0 ? 0.0 : $whiteness / $sum;

            return [$gray, $gray, $gray];
        }

        [$r, $g, $b] = $this->hslToRgb($hueDegrees, 1.0, 0.5);

        $factor = 1.0 - $whiteness - $blackness;

        return [
            $r * $factor + $whiteness,
            $g * $factor + $whiteness,
            $b * $factor + $whiteness,
        ];
    }

    public function rgbToOklch(RgbColor $rgb): OklchColor
    {
        return $this->normalizedRgbToOklch(new RgbColor(
            r: ($rgb->r ?? 0.0) / 255.0,
            g: ($rgb->g ?? 0.0) / 255.0,
            b: ($rgb->b ?? 0.0) / 255.0,
            a: $this->clamp($rgb->a, 1.0)
        ), true);
    }

    public function oklchToSrgb(OklchColor $oklch): RgbColor
    {
        $l = $this->clamp($oklch->l ?? 0.0, 100.0) / 100.0;
        $c = max(0.0, $oklch->c ?? 0.0);
        $a = $this->clamp($oklch->a, 1.0);

        [$labA, $labB] = $this->polarToCartesian($c, $oklch->h ?? 0.0);

        [$r, $g, $b] = $this->oklabToSrgb($l, $labA, $labB);

        return new RgbColor(r: $r, g: $g, b: $b, a: $a);
    }

    public function oklchToSrgbUnclamped(OklchColor $oklch): RgbColor
    {
        $l = ($oklch->l ?? 0.0) / 100.0;
        $c = max(0.0, $oklch->c ?? 0.0);

        [$labA, $labB] = $this->polarToCartesian($c, $oklch->h ?? 0.0);

        [$r, $g, $b] = $this->oklabToSrgbUnclamped($l, $labA, $labB);

        return new RgbColor(r: $r, g: $g, b: $b, a: $oklch->a);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function oklabToSrgb(float $l, float $a, float $b): array
    {
        [$rLinear, $gLinear, $bLinear] = $this->oklabToLinearSrgb($l, $a, $b);

        return [
            $this->linearToSrgb($rLinear),
            $this->linearToSrgb($gLinear),
            $this->linearToSrgb($bLinear),
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function labToSrgb(float $l, float $a, float $b): array
    {
        $xyz50 = $this->labToXyzD50($l, $a, $b);

        [$x65, $y65, $z65] = $this->xyzD50ToD65($xyz50->x, $xyz50->y, $xyz50->z);

        return $this->xyzD65ToSrgbChannels($x65, $y65, $z65);
    }

    public function labToRgbColor(LabColor $lab): RgbColor
    {
        return $this->labChannelsToSrgba(
            $lab->lValue(),
            $lab->aValue(),
            $lab->bValue(),
            $lab->alpha
        );
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function oklabToSrgbUnclamped(float $l, float $a, float $b): array
    {
        [$rLinear, $gLinear, $bLinear] = $this->oklabToLinearSrgb($l, $a, $b);

        return [
            $this->linearToSrgbUnclamped($rLinear),
            $this->linearToSrgbUnclamped($gLinear),
            $this->linearToSrgbUnclamped($bLinear),
        ];
    }

    public function interpolateHue(float $h1, float $h2, float $p): float
    {
        $delta = fmod(($h2 - $h1) + 540.0, 360.0) - 180.0;
        $mixed = $h1 + (1.0 - $p) * $delta;

        return $this->normalizeHue($mixed);
    }

    public function labF(float $value): float
    {
        if ($value > self::LAB_EPSILON) {
            return $value ** (1.0 / 3.0);
        }

        return (self::LAB_KAPPA * $value + self::LAB_DELTA) / self::LAB_SCALE;
    }

    public function trimFloat(float $value, int $precision = 6): string
    {
        $rounded = round($value, $precision);
        $text    = sprintf('%.' . $precision . 'f', $rounded);
        $text    = rtrim($text, '0');

        return rtrim($text, '.');
    }

    public function mixChannel(float|null $a, float|null $b, float $p): float
    {
        return (($a ?? 0.0) * $p) + (($b ?? 0.0) * (1.0 - $p));
    }

    public function scaleLinear(float $current, float $amountPercent, float $maxValue): float
    {
        $delta = $amountPercent / 100.0;

        if ($delta >= 0.0) {
            return $current + (($maxValue - $current) * $delta);
        }

        return $current + ($current * $delta);
    }

    public function hueFromNormalizedRgb(NormalizedRgbChannels $channels): float
    {
        if ($channels->delta <= 0.0) {
            return 0.0;
        }

        if ($channels->max === $channels->r) {
            $h = 60.0 * (($channels->g - $channels->b) / $channels->delta);

            if ($channels->g < $channels->b) {
                $h += 360.0;
            }

            return $this->normalizeHue($h);
        }

        if ($channels->max === $channels->g) {
            return $this->normalizeHue(60.0 * ((($channels->b - $channels->r) / $channels->delta) + 2.0));
        }

        return $this->normalizeHue(60.0 * ((($channels->r - $channels->g) / $channels->delta) + 4.0));
    }

    public function isAchromaticRgb(RgbColor $rgb): bool
    {
        return abs(($rgb->r ?? 0.0) - ($rgb->g ?? 0.0)) <= 0.000001
            && abs(($rgb->g ?? 0.0) - ($rgb->b ?? 0.0)) <= 0.000001;
    }

    public function rgbToXyzD50(RgbColor $rgb): XyzColor
    {
        $xyz = $this->rgbToXyzD65($rgb);

        [$x, $y, $z] = $this->xyzD65ToD50($xyz->x, $xyz->y, $xyz->z);

        return new XyzColor(x: $x, y: $y, z: $z);
    }

    public function rgbToXyzD65(RgbColor $rgb): XyzColor
    {
        return $this->linearSrgbChannelsToXyzD65(
            $this->srgbToLinear(($rgb->r ?? 0.0) / 255.0),
            $this->srgbToLinear(($rgb->g ?? 0.0) / 255.0),
            $this->srgbToLinear(($rgb->b ?? 0.0) / 255.0)
        );
    }

    public function srgbChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->linearSrgbChannelsToXyzD65(
            $this->srgbToLinear($r),
            $this->srgbToLinear($g),
            $this->srgbToLinear($b)
        );
    }

    public function linearSrgbChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return new XyzColor(
            x: 0.41239079926595934 * $r + 0.35758433938387796 * $g + 0.1804807884018343 * $b,
            y: 0.21263900587151027 * $r + 0.7151686787677559 * $g + 0.07219231536073371 * $b,
            z: 0.01933081871559185 * $r + 0.11919477979462599 * $g + 0.9505321522496607 * $b
        );
    }

    public function linearDisplayP3ChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return new XyzColor(
            x: 0.4865709486482162 * $r + 0.26566769316909306 * $g + 0.1982172852343625 * $b,
            y: 0.2289745640697488 * $r + 0.6917385218365064 * $g + 0.079286914093745 * $b,
            z: 0.04511338185890264 * $g + 1.043944368900976 * $b
        );
    }

    public function displayP3ChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->linearDisplayP3ChannelsToXyzD65(
            $this->srgbToLinear($r),
            $this->srgbToLinear($g),
            $this->srgbToLinear($b)
        );
    }

    public function srgbChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return new RgbColor(
            r: $this->clamp($r, 1.0),
            g: $this->clamp($g, 1.0),
            b: $this->clamp($b, 1.0),
            a: $opacity
        );
    }

    public function linearSrgbChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return new RgbColor(
            r: $this->linearToSrgb($r),
            g: $this->linearToSrgb($g),
            b: $this->linearToSrgb($b),
            a: $opacity
        );
    }

    public function displayP3ChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->linearDisplayP3ChannelsToSrgba(
            $this->srgbToLinear($r),
            $this->srgbToLinear($g),
            $this->srgbToLinear($b),
            $opacity
        );
    }

    public function linearDisplayP3ChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->xyzD65ToSrgba($this->linearDisplayP3ChannelsToXyzD65($r, $g, $b), $opacity);
    }

    public function xyzD65ToSrgba(XyzColor $xyz, float $opacity): RgbColor
    {
        [$r, $g, $b] = $this->xyzD65ToSrgbChannels($xyz->x, $xyz->y, $xyz->z);

        return new RgbColor(
            r: $r,
            g: $g,
            b: $b,
            a: $opacity
        );
    }

    public function xyzD50ToSrgba(XyzColor $xyz, float $opacity): RgbColor
    {
        return $this->xyzD65ToSrgba($this->xyzD50ToXyzD65($xyz), $opacity);
    }

    public function a98RgbChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        $r = $this->clamp($r, 1.0) ** (563.0 / 256.0);
        $g = $this->clamp($g, 1.0) ** (563.0 / 256.0);
        $b = $this->clamp($b, 1.0) ** (563.0 / 256.0);

        return new XyzColor(
            x: 0.5767309 * $r + 0.1855540 * $g + 0.1881852 * $b,
            y: 0.2973769 * $r + 0.6273491 * $g + 0.0752741 * $b,
            z: 0.0270343 * $r + 0.0706872 * $g + 0.9911085 * $b
        );
    }

    public function a98RgbChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->xyzD65ToSrgba($this->a98RgbChannelsToXyzD65($r, $g, $b), $opacity);
    }

    public function prophotoRgbChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        $r = $this->prophotoToLinear($r);
        $g = $this->prophotoToLinear($g);
        $b = $this->prophotoToLinear($b);

        [$x, $y, $z] = $this->xyzD50ToD65(
            0.7976749 * $r + 0.1351917 * $g + 0.0313534 * $b,
            0.2880402 * $r + 0.7118741 * $g + 0.0000857 * $b,
            0.8252100 * $b
        );

        return new XyzColor($x, $y, $z);
    }

    public function prophotoRgbChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->xyzD65ToSrgba($this->prophotoRgbChannelsToXyzD65($r, $g, $b), $opacity);
    }

    public function rec2020ChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        $r = $this->rec2020ToLinear($r);
        $g = $this->rec2020ToLinear($g);
        $b = $this->rec2020ToLinear($b);

        return new XyzColor(
            x: 0.6369580483 * $r + 0.1446169036 * $g + 0.1688809752 * $b,
            y: 0.2627002120 * $r + 0.6779980715 * $g + 0.0593017165 * $b,
            z: 0.0280726930 * $g + 1.0609850577 * $b
        );
    }

    public function rec2020ChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->xyzD65ToSrgba($this->rec2020ChannelsToXyzD65($r, $g, $b), $opacity);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToLinearDisplayP3(XyzColor $xyz): array
    {
        return [
            2.493496911941425 * $xyz->x - 0.931383617919124 * $xyz->y - 0.402710784450717 * $xyz->z,
            -0.829488969561575 * $xyz->x + 1.762664060318346 * $xyz->y + 0.023624685841943 * $xyz->z,
            0.035845830243784 * $xyz->x - 0.076172389268041 * $xyz->y + 0.956884524007687 * $xyz->z,
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToDisplayP3(RgbColor $rgb): array
    {
        return $this->xyzD65ToDisplayP3($this->rgbToXyzD65($rgb));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToDisplayP3(XyzColor $xyz): array
    {
        [$r, $g, $b] = $this->xyzD65ToLinearDisplayP3($xyz);

        return [
            $this->linearToSrgb($r),
            $this->linearToSrgb($g),
            $this->linearToSrgb($b),
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToA98Rgb(RgbColor $rgb): array
    {
        return $this->xyzD65ToA98Rgb($this->rgbToXyzD65($rgb));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToA98Rgb(XyzColor $xyz): array
    {
        return [
            $this->linearToA98Rgb(
                2.0415879038107465 * $xyz->x - 0.5650069742788597 * $xyz->y - 0.3447313507783297 * $xyz->z
            ),
            $this->linearToA98Rgb(
                -0.9692436362808798 * $xyz->x + 1.8759675015077206 * $xyz->y + 0.0415550574071756 * $xyz->z
            ),
            $this->linearToA98Rgb(
                0.0134442806320312 * $xyz->x - 0.1183623922310184 * $xyz->y + 1.0151749943912054 * $xyz->z
            ),
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToProphotoRgb(RgbColor $rgb): array
    {
        $xyz = $this->rgbToXyzD65($rgb);

        [$x, $y, $z] = $this->xyzD65ToD50($xyz->x, $xyz->y, $xyz->z);

        return $this->xyzD50ToProphotoRgb(new XyzColor($x, $y, $z));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD50ToProphotoRgb(XyzColor $xyz): array
    {
        return [
            $this->linearToProphotoRgb(
                1.3457989731028281 * $xyz->x - 0.2555801000799753 * $xyz->y - 0.0511062850675340 * $xyz->z
            ),
            $this->linearToProphotoRgb(
                -0.5446224939028347 * $xyz->x + 1.5082327413132781 * $xyz->y + 0.0205360323914797 * $xyz->z
            ),
            $this->linearToProphotoRgb(1.2119675456389454 * $xyz->z),
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToRec2020(RgbColor $rgb): array
    {
        return $this->xyzD65ToRec2020($this->rgbToXyzD65($rgb));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToRec2020(XyzColor $xyz): array
    {
        return [
            $this->linearToRec2020(
                1.716651187971268 * $xyz->x - 0.355670783776392 * $xyz->y - 0.253366281373660 * $xyz->z
            ),
            $this->linearToRec2020(
                -0.666684351832489 * $xyz->x + 1.616481236634939 * $xyz->y + 0.015768545813911 * $xyz->z
            ),
            $this->linearToRec2020(
                0.017639857445311 * $xyz->x - 0.042770613257809 * $xyz->y + 0.942103121235474 * $xyz->z
            ),
        ];
    }

    public function labToXyzD50(float $l, float $a, float $b): XyzColor
    {
        $fy = ($l + self::LAB_DELTA) / self::LAB_SCALE;
        $fx = $fy + ($a / self::LAB_A_FACTOR);
        $fz = $fy - ($b / self::LAB_B_FACTOR);

        $xr = $fx ** 3.0 > self::LAB_EPSILON ? $fx ** 3.0 : ((self::LAB_SCALE * $fx) - self::LAB_DELTA) / self::LAB_KAPPA;
        $yr = $l > (self::LAB_KAPPA * self::LAB_EPSILON) ? (($l + self::LAB_DELTA) / self::LAB_SCALE) ** 3.0 : $l / self::LAB_KAPPA;
        $zr = $fz ** 3.0 > self::LAB_EPSILON ? $fz ** 3.0 : ((self::LAB_SCALE * $fz) - self::LAB_DELTA) / self::LAB_KAPPA;

        return new XyzColor(
            x: $xr * self::D50_WHITE_X,
            y: $yr,
            z: $zr * self::D50_WHITE_Z
        );
    }

    public function labChannelsToSrgba(float $l, float $a, float $b, float $opacity): RgbColor
    {
        return $this->xyzD50ToSrgba($this->labToXyzD50($l, $a, $b), $opacity);
    }

    public function labChannelsToXyzD65(float $l, float $a, float $b): XyzColor
    {
        return $this->xyzD50ToXyzD65($this->labToXyzD50($l, $a, $b));
    }

    public function lchChannelsToSrgba(float $l, float $c, float $h, float $opacity): RgbColor
    {
        [$labA, $labB] = $this->polarToCartesian($c, $h);

        return $this->labChannelsToSrgba($l, $labA, $labB, $opacity);
    }

    public function lchChannelsToXyzD65(float $l, float $c, float $h): XyzColor
    {
        [$labA, $labB] = $this->polarToCartesian($c, $h);

        return $this->labChannelsToXyzD65($l, $labA, $labB);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzToLabD50(XyzColor $xyz): array
    {
        $x = $xyz->x / self::D50_WHITE_X;
        $y = $xyz->y / self::D50_WHITE_Y;
        $z = $xyz->z / self::D50_WHITE_Z;

        $fx = $x > self::LAB_EPSILON ? $x ** (1.0 / 3.0) : (self::LAB_KAPPA * $x + self::LAB_DELTA) / self::LAB_SCALE;
        $fy = $y > self::LAB_EPSILON ? $y ** (1.0 / 3.0) : (self::LAB_KAPPA * $y + self::LAB_DELTA) / self::LAB_SCALE;
        $fz = $z > self::LAB_EPSILON ? $z ** (1.0 / 3.0) : (self::LAB_KAPPA * $z + self::LAB_DELTA) / self::LAB_SCALE;

        $l = (self::LAB_SCALE * $fy) - self::LAB_DELTA;
        $a = self::LAB_A_FACTOR * ($fx - $fy);
        $b = self::LAB_B_FACTOR * ($fy - $fz);

        return [$l, $a, $b];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzToLchD50(XyzColor $xyz): array
    {
        [$l, $a, $b] = $this->xyzToLabD50($xyz);

        [$c, $h] = $this->cartesianToPolar($a, $b);

        return [$l, $c, $h];
    }

    public function xyzD50ToLabColor(XyzColor $xyz, float $alpha = 1.0): LabColor
    {
        [$l, $a, $b] = $this->xyzToLabD50($xyz);

        return new LabColor(l: $l, a: $a, b: $b, alpha: $alpha);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD50ToD65(float $x, float $y, float $z): array
    {
        return [
            0.955473421488075 * $x - 0.02309845494876471 * $y + 0.06325924320057072 * $z,
            -0.0283697093338637 * $x + 1.0099953980813041 * $y + 0.021041441191917323 * $z,
            0.012314014864481998 * $x - 0.020507649298898964 * $y + 1.330365926242124 * $z,
        ];
    }

    public function xyzD65ToOklch(XyzColor $xyz, float $alpha = 1.0): OklchColor
    {
        [$l, $a, $b] = $this->xyzToOklabD65($xyz);

        return $this->oklabComponentsToOklch($l, $a, $b, $alpha);
    }

    public function xyzD65ToOklabColor(XyzColor $xyz, float $alpha = 1.0): OklabColor
    {
        [$l, $a, $b] = $this->xyzToOklabD65($xyz);

        return new OklabColor(
            l: $l * 100.0,
            a: $a,
            b: $b,
            alpha: $alpha
        );
    }

    public function oklabChannelsToXyzD65(float $l, float $a, float $b): XyzColor
    {
        $lPrime = $l + 0.3963377773761749 * $a + 0.2158037573099136 * $b;
        $mPrime = $l - 0.1055613458156586 * $a - 0.0638541728258133 * $b;
        $sPrime = $l - 0.0894841775298119 * $a - 1.2914855480194092 * $b;

        $lLinear = $lPrime ** 3.0;
        $mLinear = $mPrime ** 3.0;
        $sLinear = $sPrime ** 3.0;

        return new XyzColor(
            x: 1.2268798758459243 * $lLinear - 0.5578149944602171 * $mLinear + 0.2813910456659647 * $sLinear,
            y: -0.0405757452148008 * $lLinear + 1.1122868032803170 * $mLinear - 0.0717110580655164 * $sLinear,
            z: -0.0763729366746601 * $lLinear - 0.4214933324022432 * $mLinear + 1.5869240198367816 * $sLinear
        );
    }

    public function oklabChannelsToSrgba(float $l, float $a, float $b, float $opacity): RgbColor
    {
        [$r, $g, $bb] = $this->oklabToSrgb($l, $a, $b);

        return new RgbColor(r: $r, g: $g, b: $bb, a: $opacity);
    }

    public function oklchChannelsToSrgba(float $l, float $c, float $h, float $opacity): RgbColor
    {
        [$labA, $labB] = $this->polarToCartesian($c, $h);

        return $this->oklabChannelsToSrgba($l, $labA, $labB, $opacity);
    }

    public function oklchChannelsToXyzD65(float $l, float $c, float $h): XyzColor
    {
        [$labA, $labB] = $this->polarToCartesian($c, $h);

        return $this->oklabChannelsToXyzD65($l, $labA, $labB);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzToOklabD65(XyzColor $xyz): array
    {
        $l = $this->cubeRoot(
            0.8190224379967030 * $xyz->x + 0.3619062600528904 * $xyz->y - 0.1288737815209879 * $xyz->z
        );
        $m = $this->cubeRoot(
            0.0329836539323885 * $xyz->x + 0.9292868615863434 * $xyz->y + 0.0361446663506424 * $xyz->z
        );
        $s = $this->cubeRoot(
            0.0481771893596242 * $xyz->x + 0.2642395317527308 * $xyz->y + 0.6335478284694309 * $xyz->z
        );

        return $this->lmsToOklab($l, $m, $s);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzToOklchD65(XyzColor $xyz): array
    {
        [$l, $a, $b] = $this->xyzToOklabD65($xyz);

        [$c, $h] = $this->cartesianToPolar($a, $b);

        return [$l, $c, $h];
    }

    public function oklchToLch(OklchColor $oklch): LchColor
    {
        $l = ($oklch->l ?? 0.0) / 100.0;

        [$a, $b] = $this->polarToCartesian($oklch->c ?? 0.0, $oklch->h ?? 0.0);

        $xyz = $this->oklabChannelsToXyzD65($l, $a, $b);

        return $this->xyzD50ToLch($this->xyzD65ToXyzD50($xyz));
    }

    public function rgbToLch(RgbColor $rgb): LchColor
    {
        $lch = $this->xyzD50ToLch($this->rgbToXyzD50($rgb));

        if ($this->isAchromaticRgb($rgb)) {
            return new LchColor(l: $lch->l, c: 0.0, h: $lch->h);
        }

        return $lch;
    }

    public function xyzD50ToLch(XyzColor $xyz): LchColor
    {
        $xr = $xyz->x / 0.9642956764295677;
        $yr = $xyz->y;
        $zr = $xyz->z / 0.8251046025104602;

        $fx = $this->labF($xr);
        $fy = $this->labF($yr);
        $fz = $this->labF($zr);

        $l  = 116.0 * $fy - 16.0;
        $a  = 500.0 * ($fx - $fy);
        $bb = 200.0 * ($fy - $fz);

        [$c, $h] = $this->cartesianToPolar($a, $bb);

        return new LchColor(l: $l, c: $c, h: $h);
    }

    public function calculateDeltaE(RgbColor $rgb1, RgbColor $rgb2): float
    {
        $oklch1 = $this->normalizedRgbToOklch($rgb1, false);
        $oklch2 = $this->normalizedRgbToOklch($rgb2, false);

        $l1 = ($oklch1->l ?? 0.0) / 100.0;
        $l2 = ($oklch2->l ?? 0.0) / 100.0;

        [$a1, $b1] = $this->polarToCartesian($oklch1->c ?? 0.0, $oklch1->h ?? 0.0);
        [$a2, $b2] = $this->polarToCartesian($oklch2->c ?? 0.0, $oklch2->h ?? 0.0);

        $deltaL = $l1 - $l2;
        $deltaA = $a1 - $a2;
        $deltaB = $b1 - $b2;

        return sqrt($deltaL * $deltaL + $deltaA * $deltaA + $deltaB * $deltaB);
    }

    public function normalizedSrgbToOklch(RgbColor $rgb, bool $clampChannels = false): OklchColor
    {
        return $this->normalizedRgbToOklch($rgb, $clampChannels);
    }

    public function normalizedRgbToOklch(RgbColor $rgb, bool $clampChannels): OklchColor
    {
        $toLinear = $clampChannels
            ? $this->srgbToLinear(...)
            : $this->srgbToLinearUnclamped(...);

        $r = $toLinear($rgb->r ?? 0.0);
        $g = $toLinear($rgb->g ?? 0.0);
        $b = $toLinear($rgb->b ?? 0.0);

        [$l, $a, $b] = $this->linearSrgbToOklab($r, $g, $b);

        return $this->oklabComponentsToOklch($l, $a, $b, $rgb->a);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToD50(float $x, float $y, float $z): array
    {
        return [
            1.0479297925449969 * $x + 0.022946870601609652 * $y - 0.05019226628920524 * $z,
            0.02962780877005599 * $x + 0.9904344267538799 * $y - 0.017073799063418826 * $z,
            -0.009243040646204504 * $x + 0.015055191490298152 * $y + 0.7518742814281371 * $z,
        ];
    }

    public function xyzD50ToXyzD65(XyzColor $xyz): XyzColor
    {
        [$x, $y, $z] = $this->xyzD50ToD65($xyz->x, $xyz->y, $xyz->z);

        return new XyzColor($x, $y, $z);
    }

    public function xyzD65ToXyzD50(XyzColor $xyz): XyzColor
    {
        [$x, $y, $z] = $this->xyzD65ToD50($xyz->x, $xyz->y, $xyz->z);

        return new XyzColor($x, $y, $z);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function linearSrgbToOklab(float $r, float $g, float $b): array
    {
        $l = $this->cubeRoot(0.4122214694707630 * $r + 0.5363325372617348 * $g + 0.0514459932675022 * $b);
        $m = $this->cubeRoot(0.2119034958178252 * $r + 0.6806995506452342 * $g + 0.1073969535369405 * $b);
        $s = $this->cubeRoot(0.0883024591900564 * $r + 0.2817188391361215 * $g + 0.6299787016738221 * $b);

        return $this->lmsToOklab($l, $m, $s);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function xyzD65ToSrgbChannels(float $x, float $y, float $z): array
    {
        return [
            $this->linearToSrgb(3.2409699419045226 * $x - 1.537383177570094 * $y - 0.4986107602930034 * $z),
            $this->linearToSrgb(-0.9692436362808796 * $x + 1.8759675015077202 * $y + 0.04155505740717559 * $z),
            $this->linearToSrgb(0.05563007969699366 * $x - 0.20397695888897652 * $y + 1.0569715142428786 * $z),
        ];
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function polarToCartesian(float $radius, float $hueDegrees): array
    {
        $hueRadians = $hueDegrees * M_PI / 180.0;

        return [$radius * cos($hueRadians), $radius * sin($hueRadians)];
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function cartesianToPolar(float $a, float $b): array
    {
        $hue = atan2($b, $a) * 180.0 / M_PI;

        if ($hue < 0.0) {
            $hue += 360.0;
        }

        return [sqrt($a * $a + $b * $b), $hue];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function oklabToLinearSrgb(float $l, float $a, float $b): array
    {
        $lPrime = 1.0000000000000002 * $l + 0.3963377773761749 * $a + 0.2158037573099136 * $b;
        $mPrime = 0.9999999999999998 * $l - 0.1055613458156585 * $a - 0.0638541728258133 * $b;
        $sPrime = $l - 0.0894841775298118 * $a - 1.2914855480194094 * $b;

        $lLinear = $lPrime * $lPrime * $lPrime;
        $mLinear = $mPrime * $mPrime * $mPrime;
        $sLinear = $sPrime * $sPrime * $sPrime;

        return [
            4.0767416360759580 * $lLinear - 3.3077115392580620 * $mLinear + 0.2309699031821042 * $sLinear,
            -1.2684379732850320 * $lLinear + 2.6097573492876890 * $mLinear - 0.3413193760026571 * $sLinear,
            -0.0041960761386755 * $lLinear - 0.7034186179359363 * $mLinear + 1.7076146940746120 * $sLinear,
        ];
    }

    private function oklabComponentsToOklch(float $l, float $a, float $b, float $alpha): OklchColor
    {
        [$chroma, $hue] = $this->cartesianToPolar($a, $b);

        return new OklchColor(
            l: $l * 100.0,
            c: max(0.0, $chroma),
            h: $this->normalizeHue($hue),
            a: $alpha
        );
    }

    private function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0.0) {
            $t += 1.0;
        }

        if ($t > 1.0) {
            $t -= 1.0;
        }

        if ($t < (1.0 / 6.0)) {
            return $p + ($q - $p) * 6.0 * $t;
        }

        if ($t < 0.5) {
            return $q;
        }

        if ($t < (2.0 / 3.0)) {
            return $p + ($q - $p) * ((2.0 / 3.0) - $t) * 6.0;
        }

        return $p;
    }

    private function linearToA98Rgb(float $value): float
    {
        return $this->clamp($value, 1.0) ** (256.0 / 563.0);
    }

    private function prophotoToLinear(float $value): float
    {
        $value = $this->clamp($value, 1.0);

        if ($value <= 16.0 / 512.0) {
            return $value / 16.0;
        }

        return $value ** 1.8;
    }

    private function rec2020ToLinear(float $value): float
    {
        $value = $this->clamp($value, 1.0);

        if ($value < 0.08145) {
            return $value / 4.5;
        }

        return (($value + 0.0993) / 1.0993) ** (1.0 / 0.45);
    }

    private function linearToProphotoRgb(float $value): float
    {
        $value = $this->clamp($value, 1.0);

        if ($value <= 1.0 / 512.0) {
            return $value * 16.0;
        }

        return $value ** (1.0 / 1.8);
    }

    private function linearToRec2020(float $value): float
    {
        $value = $this->clamp($value, 1.0);

        if ($value < 0.0181) {
            return $value * 4.5;
        }

        return 1.0993 * $value ** 0.45 - 0.0993;
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function lmsToOklab(float $l, float $m, float $s): array
    {
        return [
            0.2104542683093140 * $l + 0.7936177747023054 * $m - 0.0040720430116193 * $s,
            1.9779985324311684 * $l - 2.4285922420485799 * $m + 0.4505937096174110 * $s,
            0.0259040424655478 * $l + 0.7827717124575296 * $m - 0.8086757549230774 * $s,
        ];
    }
}
