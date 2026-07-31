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
        return $this->srgbToLinearUnclamped($value);
    }

    public function srgbToLinearUnclamped(float|null $value): float
    {
        $value ??= 0.0;

        $abs = abs($value);

        if ($abs <= 0.04045) {
            return $value / 12.92;
        }

        $linear = (($abs + 0.055) / 1.055) ** 2.4;

        return $value >= 0.0 ? $linear : -$linear;
    }

    public function linearToSrgb(float $value): float
    {
        return $this->linearToSrgbUnclamped($value);
    }

    public function linearToSrgbUnclamped(float $value): float
    {
        $abs = abs($value);

        if ($abs <= 0.0031308) {
            return 12.92 * $value;
        }

        $companded = 1.055 * $abs ** (1.0 / 2.4) - 0.055;

        return $value >= 0.0 ? $companded : -$companded;
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
            return [$lightness, $lightness, $lightness];
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
            a: $rgb->a
        ), true);
    }

    public function oklchToSrgb(OklchColor $oklch): RgbColor
    {
        $l = ($oklch->l ?? 0.0) / 100.0;
        $c = max(0.0, $oklch->c ?? 0.0);

        [$labA, $labB] = $this->polarToCartesian($c, $oklch->h ?? 0.0);

        [$r, $g, $b] = $this->oklabToSrgb($l, $labA, $labB);

        return new RgbColor(r: $r, g: $g, b: $b, a: $oklch->a);
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

        [$x65, $y65, $z65] = $this->xyzD50ToD65($xyz50->xValue(), $xyz50->yValue(), $xyz50->zValue());

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

        [$x, $y, $z] = $this->xyzD65ToD50($xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0);

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
            x: 0.41239079926595950 * $r + 0.35758433938387796 * $g + 0.18048078840183430 * $b,
            y: 0.21263900587151036 * $r + 0.71516867876775590 * $g + 0.07219231536073371 * $b,
            z: 0.01933081871559185 * $r + 0.11919477979462598 * $g + 0.95053215224966060 * $b
        );
    }

    public function linearDisplayP3ChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return new XyzColor(
            x: 0.48657094864821626 * $r + 0.26566769316909294 * $g + 0.19821728523436250 * $b,
            y: 0.22897456406974884 * $r + 0.69173852183650620 * $g + 0.07928691409374500 * $b,
            z: 0.00000000000000000 * $r + 0.04511338185890257 * $g + 1.04394436890097570 * $b
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
        [$r, $g, $b] = $this->xyzD65ToSrgbChannels($xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0);

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
        $r = ($r >= 0.0 ? 1.0 : -1.0) * (abs($r) ** (563.0 / 256.0));
        $g = ($g >= 0.0 ? 1.0 : -1.0) * (abs($g) ** (563.0 / 256.0));
        $b = ($b >= 0.0 ? 1.0 : -1.0) * (abs($b) ** (563.0 / 256.0));

        return new XyzColor(
            x: 0.57666904291013080 * $r + 0.18555823790654627 * $g + 0.18822864623499472 * $b,
            y: 0.29734497525053616 * $r + 0.62736356625546600 * $g + 0.07529145849399789 * $b,
            z: 0.02703136138641237 * $r + 0.07068885253582714 * $g + 0.99133753683763890 * $b
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
            0.79776664490064230 * $r + 0.13518129740053308 * $g + 0.03134773412839220 * $b,
            0.28807482881940130 * $r + 0.71183523424187300 * $g + 0.00008993693872564 * $b,
            0.82510460251046020 * $b
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
            x: 0.63695804830129130 * $r + 0.14461690358620838 * $g + 0.16888097516417205 * $b,
            y: 0.26270021201126703 * $r + 0.67799807151887100 * $g + 0.05930171646986194 * $b,
            z: 0.00000000000000000 * $r + 0.02807269304908750 * $g + 1.06098505771079090 * $b
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
        $x = $xyz->x ?? 0.0;
        $y = $xyz->y ?? 0.0;
        $z = $xyz->z ?? 0.0;

        return [
            2.49349691194142450 * $x - 0.93138361791912360 * $y - 0.40271078445071684 * $z,
            -0.82948896956157490 * $x + 1.76266406031834680 * $y + 0.02362468584194359 * $z,
            0.03584583024378433 * $x - 0.07617238926804170 * $y + 0.95688452400768730 * $z,
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
        $x = $xyz->x ?? 0.0;
        $y = $xyz->y ?? 0.0;
        $z = $xyz->z ?? 0.0;

        return [
            $this->linearToA98Rgb(
                2.04158790381074600 * $x - 0.56500697427885960 * $y - 0.34473135077832950 * $z
            ),
            $this->linearToA98Rgb(
                -0.96924363628087980 * $x + 1.87596750150772060 * $y + 0.04155505740717561 * $z
            ),
            $this->linearToA98Rgb(
                0.01344428063203102 * $x - 0.11836239223101823 * $y + 1.01517499439120540 * $z
            ),
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToProphotoRgb(RgbColor $rgb): array
    {
        $xyz = $this->rgbToXyzD65($rgb);

        [$x, $y, $z] = $this->xyzD65ToD50($xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0);

        return $this->xyzD50ToProphotoRgb(new XyzColor($x, $y, $z));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD50ToProphotoRgb(XyzColor $xyz): array
    {
        $x = $xyz->x ?? 0.0;
        $y = $xyz->y ?? 0.0;
        $z = $xyz->z ?? 0.0;

        return [
            $this->linearToProphotoRgb(
                1.34578688164715830 * $x - 0.25557208737979464 * $y - 0.05110186497554526 * $z
            ),
            $this->linearToProphotoRgb(
                -0.54463070512490190 * $x + 1.50824774284514680 * $y + 0.02052744743642139 * $z
            ),
            $this->linearToProphotoRgb(0.00000000000000000 * $x + 0.00000000000000000 * $y + 1.21196754563894520 * $z),
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
        $x = $xyz->x ?? 0.0;
        $y = $xyz->y ?? 0.0;
        $z = $xyz->z ?? 0.0;

        return [
            $this->linearToRec2020(
                1.71665118797126760 * $x - 0.35567078377639240 * $y - 0.25336628137365980 * $z
            ),
            $this->linearToRec2020(
                -0.66668435183248900 * $x + 1.61648123663493900 * $y + 0.01576854581391113 * $z
            ),
            $this->linearToRec2020(
                0.01763985744531091 * $x - 0.04277061325780865 * $y + 0.94210312123547400 * $z
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
        $x = ($xyz->x ?? 0.0) / self::D50_WHITE_X;
        $y = ($xyz->y ?? 0.0) / self::D50_WHITE_Y;
        $z = ($xyz->z ?? 0.0) / self::D50_WHITE_Z;

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
            0.95547342148807520 * $x - 0.02309845494876452 * $y + 0.06325924320057065 * $z,
            -0.02836970933386358 * $x + 1.00999539808130410 * $y + 0.02104144119191730 * $z,
            0.01231401486448199 * $x - 0.02050764929889898 * $y + 1.33036592624212400 * $z,
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
        $lPrime = $l + 0.39633777737617490 * $a + 0.21580375730991360 * $b;
        $mPrime = $l - 0.10556134581565854 * $a - 0.06385417282581334 * $b;
        $sPrime = $l - 0.08948417752981180 * $a - 1.29148554801940940 * $b;

        $lLinear = $lPrime ** 3.0;
        $mLinear = $mPrime ** 3.0;
        $sLinear = $sPrime ** 3.0;

        return new XyzColor(
            x: 1.22687987584592430 * $lLinear - 0.55781499446021710 * $mLinear + 0.28139104566596460 * $sLinear,
            y: -0.04057574521480084 * $lLinear + 1.11228680328031730 * $mLinear - 0.07171105806551635 * $sLinear,
            z: -0.07637293667466007 * $lLinear - 0.42149333240224324 * $mLinear + 1.58692401983678180 * $sLinear
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
        $x = $xyz->x ?? 0.0;
        $y = $xyz->y ?? 0.0;
        $z = $xyz->z ?? 0.0;

        $l = $this->cubeRoot(
            0.81902243799670300 * $x + 0.36190626005289034 * $y - 0.12887378152098788 * $z
        );
        $m = $this->cubeRoot(
            0.03298365393238846 * $x + 0.92928686158634330 * $y + 0.03614466635064235 * $z
        );
        $s = $this->cubeRoot(
            0.04817718935962420 * $x + 0.26423953175273080 * $y + 0.63354782846943080 * $z
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
        $xr = ($xyz->x ?? 0.0) / 0.9642956764295677;
        $yr = $xyz->y ?? 0.0;
        $zr = ($xyz->z ?? 0.0) / 0.8251046025104602;

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
            1.04792979254499660 * $x + 0.02294687060160952 * $y - 0.05019226628920519 * $z,
            0.02962780877005567 * $x + 0.99043442675388000 * $y - 0.01707379906341879 * $z,
            -0.00924304064620452 * $x + 0.01505519149029816 * $y + 0.75187428142813700 * $z,
        ];
    }

    public function xyzD50ToXyzD65(XyzColor $xyz): XyzColor
    {
        [$x, $y, $z] = $this->xyzD50ToD65($xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0);

        return new XyzColor($x, $y, $z);
    }

    public function xyzD65ToXyzD50(XyzColor $xyz): XyzColor
    {
        [$x, $y, $z] = $this->xyzD65ToD50($xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0);

        return new XyzColor($x, $y, $z);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function linearSrgbToOklab(float $r, float $g, float $b): array
    {
        $l = $this->cubeRoot(0.41222146947076300 * $r + 0.53633253726173480 * $g + 0.05144599326750220 * $b);
        $m = $this->cubeRoot(0.21190349581782520 * $r + 0.68069955064523420 * $g + 0.10739695353694050 * $b);
        $s = $this->cubeRoot(0.08830245919005641 * $r + 0.28171883913612150 * $g + 0.62997870167382210 * $b);

        return $this->lmsToOklab($l, $m, $s);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function xyzD65ToSrgbChannels(float $x, float $y, float $z): array
    {
        return [
            $this->linearToSrgb(3.24096994190452130 * $x - 1.53738317757009350 * $y - 0.49861076029300330 * $z),
            $this->linearToSrgb(-0.96924363628087980 * $x + 1.87596750150772060 * $y + 0.04155505740717561 * $z),
            $this->linearToSrgb(0.05563007969699360 * $x - 0.20397695888897657 * $y + 1.05697151424287860 * $z),
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
        $lPrime = 1.00000000000000020 * $l + 0.39633777737617490 * $a + 0.21580375730991360 * $b;
        $mPrime = 0.99999999999999980 * $l - 0.10556134581565854 * $a - 0.06385417282581334 * $b;
        $sPrime = 0.99999999999999990 * $l - 0.08948417752981180 * $a - 1.29148554801940940 * $b;

        $lLinear = $lPrime * $lPrime * $lPrime;
        $mLinear = $mPrime * $mPrime * $mPrime;
        $sLinear = $sPrime * $sPrime * $sPrime;

        return [
            4.07674163607595800 * $lLinear - 3.30771153925806200 * $mLinear + 0.23096990318210417 * $sLinear,
            -1.26843797328503200 * $lLinear + 2.60975734928768900 * $mLinear - 0.34131937600265710 * $sLinear,
            -0.00419607613867551 * $lLinear - 0.70341861793593630 * $mLinear + 1.70761469407461200 * $sLinear,
        ];
    }

    private function oklabComponentsToOklch(float $l, float $a, float $b, float $alpha): OklchColor
    {
        [$chroma, $hue] = $this->cartesianToPolar($a, $b);

        return new OklchColor(
            l: $l * 100.0,
            c: max(0.0, $chroma),
            h: $chroma < 1e-6 ? null : $this->normalizeHue($hue),
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
        $abs = abs($value);

        return ($value >= 0.0 ? 1.0 : -1.0) * ($abs ** (256.0 / 563.0));
    }

    private function prophotoToLinear(float $value): float
    {
        $abs = abs($value);

        if ($abs <= 16.0 / 512.0) {
            return $value / 16.0;
        }

        return ($value >= 0.0 ? 1.0 : -1.0) * ($abs ** 1.8);
    }

    private function rec2020ToLinear(float $value): float
    {
        $abs = abs($value);

        return ($value >= 0.0 ? 1.0 : -1.0) * ($abs ** 2.4);
    }

    private function linearToProphotoRgb(float $value): float
    {
        $abs = abs($value);

        if ($abs <= 1.0 / 512.0) {
            return $value * 16.0;
        }

        return ($value >= 0.0 ? 1.0 : -1.0) * ($abs ** (1.0 / 1.8));
    }

    private function linearToRec2020(float $value): float
    {
        $abs = abs($value);

        return ($value >= 0.0 ? 1.0 : -1.0) * ($abs ** (1.0 / 2.4));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function lmsToOklab(float $l, float $m, float $s): array
    {
        return [
            0.21045426830931400 * $l + 0.79361777470230540 * $m - 0.00407204301161930 * $s,
            1.97799853243116840 * $l - 2.42859224204858000 * $m + 0.45059370961741100 * $s,
            0.02590404246554780 * $l + 0.78277171245752960 * $m - 0.80867575492307740 * $s,
        ];
    }
}
