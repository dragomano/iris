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

    // @pest-mutate-ignore
    private const ACHROMATIC_CHROMA_EPSILON = 1e-6;

    // @pest-mutate-ignore
    private const ACHROMATIC_CHANNEL_EPSILON = 1e-9;

    private const M_LIN_SRGB_TO_XYZ = [
        [0.41239079926595951,  0.35758433938387796, 0.18048078840183429],
        [0.21263900587151036,  0.71516867876775592, 0.072192315360733714],
        [0.019330818715591849, 0.11919477979462599, 0.95053215224966059],
    ];

    private const M_XYZ_TO_LIN_SRGB = [
        [3.2409699419045213, -1.5373831775700935, -0.49861076029300327],
        [-0.96924363628087984, 1.8759675015077206, 0.041555057407175612],
        [0.055630079696993608, -0.20397695888897657, 1.0569715142428786],
    ];

    private const M_LIN_P3_TO_XYZ = [
        [0.48657094864821626, 0.26566769316909294,  0.19821728523436249],
        [0.22897456406974884, 0.69173852183650619,  0.079286914093744998],
        [0.0,                 0.045113381858902575, 1.0439443689009757],
    ];

    private const M_XYZ_TO_LIN_P3 = [
        [2.4934969119414245, -0.93138361791912361, -0.40271078445071684],
        [-0.82948896956157503, 1.7626640603183468, 0.023624685841943591],
        [0.035845830243784335, -0.076172389268041707, 0.95688452400768731],
    ];

    private const M_LIN_A98_TO_XYZ = [
        [0.57666904291013077,  0.18555823790654627,  0.18822864623499472],
        [0.29734497525053616,  0.62736356625546597,  0.07529145849399789],
        [0.027031361386412378, 0.070688852535827143, 0.99133753683763892],
    ];

    private const M_XYZ_TO_LIN_A98 = [
        [2.0415879038107461, -0.5650069742788596, -0.3447313507783295],
        [-0.96924363628087984, 1.8759675015077206, 0.041555057407175612],
        [0.013444280632031024, -0.11836239223101824, 1.0151749943912054],
    ];

    private const M_LIN_REC2020_TO_XYZ = [
        [0.63695804830129132, 0.14461690358620838,  0.16888097516417205],
        [0.26270021201126703, 0.67799807151887104,  0.059301716469861945],
        [0.0,                 0.028072693049087508, 1.0609850577107909],
    ];

    private const M_XYZ_TO_LIN_REC2020 = [
        [1.7166511879712676, -0.35567078377639239, -0.2533662813736598],
        [-0.66668435183248898, 1.616481236634939, 0.015768545813911131],
        [0.017639857445310915, -0.042770613257808655, 0.94210312123547402],
    ];

    private const M_LIN_PROPHOTO_TO_XYZ_D50 = [
        [0.79776664490064231, 0.13518129740053311, 0.031347734128392202],
        [0.28807482881940127, 0.711835234241873,   8.9936938725599993e-05],
        [0.0,                 0.0,                 0.82510460251046025],
    ];

    private const M_XYZ_D50_TO_LIN_PROPHOTO = [
        [1.3457868816471583, -0.25557208737979459, -0.051101864975545301],
        [-0.54463070512490186, 1.5082477428451468, 0.0205274474364214],
        [0.0, 0.0, 1.2119675456389452],
    ];

    private const M_D65_TO_D50 = [
        [1.0479297925449969, 0.022946870601609701, -0.050192266289205201],
        [0.029627808770056, 0.99043442675387994, -0.017073799063418799],
        [-0.0092430406462045006, 0.0150551914902982, 0.75187428142813706],
    ];

    private const M_D50_TO_D65 = [
        [0.95547342148807501, -0.0230984549487647, 0.063259243200570706],
        [-0.0283697093338637, 1.0099953980813041, 0.021041441191917299],
        [0.012314014864482, -0.020507649298898999, 1.3303659262421239],
    ];

    private const M_XYZ_TO_LMS = [
        [0.81902243799670305, 0.36190626005289039, -0.12887378152098791],
        [0.032983653932388501, 0.92928686158634344, 0.036144666350642403],
        [0.048177189359624201, 0.26423953175273079, 0.63354782846943092],
    ];

    private const M_LMS_TO_OKLAB = [
        [0.21045426830931399, 0.79361777470230543, -0.0040720430116193002],
        [1.9779985324311684, -2.4285922420485799, 0.45059370961741102],
        [0.0259040424655478, 0.78277171245752963, -0.80867575492307742],
    ];

    private const M_OKLAB_TO_LMS = [
        [1.0, 0.39633777737617493, 0.21580375730991361],
        [1.0, -0.1055613458156586, -0.063854172825813293],
        [1.0, -0.089484177529811901, -1.2914855480194092],
    ];

    private const M_LMS_TO_XYZ = [
        [1.2268798758459243, -0.5578149944602171, 0.28139104566596468],
        [-0.040575745214800799, 1.112286803280317, -0.071711058065516406],
        [-0.076372936674660094, -0.42149333240224318, 1.5869240198367816],
    ];

    private const M_LIN_SRGB_TO_LMS = [
        [0.41222146947076300, 0.53633253726173480, 0.05144599326750220],
        [0.21190349581782520, 0.68069955064523420, 0.10739695353694050],
        [0.08830245919005641, 0.28171883913612150, 0.62997870167382210],
    ];

    private const M_LMS_TO_LIN_SRGB = [
        [4.07674163607595800, -3.30771153925806200, 0.23096990318210417],
        [-1.26843797328503200, 2.60975734928768900, -0.34131937600265710],
        [-0.00419607613867551, -0.70341861793593630, 1.70761469407461200],
    ];

    public function clamp(?float $value, float $max): float
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

    public function linSrgb(?float $value): float
    {
        $value ??= 0.0;

        $abs = abs($value);

        if ($abs <= 0.04045) {
            return $value / 12.92;
        }

        $linear = (($abs + 0.055) / 1.055) ** 2.4;

        return $value >= 0.0 ? $linear : -$linear;
    }

    public function gamSrgb(float $value): float
    {
        $abs = abs($value);

        if ($abs <= 0.0031308) {
            return 12.92 * $value;
        }

        $companded = (1.055 * ($abs ** (1.0 / 2.4))) - 0.055;

        return $value >= 0.0 ? $companded : -$companded;
    }

    public function linP3(float $value): float
    {
        return $this->linSrgb($value);
    }

    public function gamP3(float $value): float
    {
        return $this->gamSrgb($value);
    }

    public function linA98(float $value): float
    {
        return ($value >= 0.0 ? 1.0 : -1.0) * (abs($value) ** (563.0 / 256.0));
    }

    public function gamA98(float $value): float
    {
        $abs = abs($value);

        return ($value >= 0.0 ? 1.0 : -1.0) * ($abs ** (256.0 / 563.0));
    }

    public function linProphoto(float $value): float
    {
        $abs = abs($value);

        if ($abs <= 16.0 / 512.0) {
            return $value / 16.0;
        }

        return ($value >= 0.0 ? 1.0 : -1.0) * ($abs ** 1.8);
    }

    public function gamProphoto(float $value): float
    {
        $abs = abs($value);

        if ($abs <= 1.0 / 512.0) {
            return $value * 16.0;
        }

        return ($value >= 0.0 ? 1.0 : -1.0) * ($abs ** (1.0 / 1.8));
    }

    public function linRec2020(float $value): float
    {
        return ($value >= 0.0 ? 1.0 : -1.0) * (abs($value) ** 2.4);
    }

    public function gamRec2020(float $value): float
    {
        return ($value >= 0.0 ? 1.0 : -1.0) * (abs($value) ** (1.0 / 2.4));
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
        $p = (2.0 * $lightness) - $q;

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
            ($r * $factor) + $whiteness,
            ($g * $factor) + $whiteness,
            ($b * $factor) + $whiteness,
        ];
    }

    public function linSrgbToXyzD65(float $r, float $g, float $b): XyzColor
    {
        [$x, $y, $z] = $this->multiply(self::M_LIN_SRGB_TO_XYZ, [$r, $g, $b]);

        return new XyzColor(x: $x, y: $y, z: $z);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToLinSrgb(XyzColor $xyz): array
    {
        return $this->multiply(self::M_XYZ_TO_LIN_SRGB, [$xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0]);
    }

    public function srgbToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->linSrgbToXyzD65($this->linSrgb($r), $this->linSrgb($g), $this->linSrgb($b));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToSrgbChannels(XyzColor $xyz): array
    {
        [$r, $g, $b] = $this->xyzD65ToLinSrgb($xyz);

        return [$this->gamSrgb($r), $this->gamSrgb($g), $this->gamSrgb($b)];
    }

    public function rgbToXyzD65(RgbColor $rgb): XyzColor
    {
        return $this->srgbToXyzD65($rgb->r ?? 0.0, $rgb->g ?? 0.0, $rgb->b ?? 0.0);
    }

    public function rgbToXyzD50(RgbColor $rgb): XyzColor
    {
        return $this->xyzD65ToXyzD50($this->rgbToXyzD65($rgb));
    }

    public function xyzD65ToRgb(XyzColor $xyz, float $opacity): RgbColor
    {
        [$r, $g, $b] = $this->xyzD65ToSrgbChannels($xyz);

        return new RgbColor(r: $r, g: $g, b: $b, a: $opacity);
    }

    public function xyzD50ToRgb(XyzColor $xyz, float $opacity): RgbColor
    {
        return $this->xyzD65ToRgb($this->xyzD50ToXyzD65($xyz), $opacity);
    }

    public function srgbChannelsToRgb(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return new RgbColor(
            r: $this->clamp($r, 1.0),
            g: $this->clamp($g, 1.0),
            b: $this->clamp($b, 1.0),
            a: $opacity,
        );
    }

    public function linSrgbChannelsToRgb(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return new RgbColor(r: $this->gamSrgb($r), g: $this->gamSrgb($g), b: $this->gamSrgb($b), a: $opacity);
    }

    public function linP3ToXyzD65(float $r, float $g, float $b): XyzColor
    {
        [$x, $y, $z] = $this->multiply(self::M_LIN_P3_TO_XYZ, [$r, $g, $b]);

        return new XyzColor(x: $x, y: $y, z: $z);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToLinP3(XyzColor $xyz): array
    {
        return $this->multiply(self::M_XYZ_TO_LIN_P3, [$xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0]);
    }

    public function p3ToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->linP3ToXyzD65($this->linP3($r), $this->linP3($g), $this->linP3($b));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToP3Channels(XyzColor $xyz): array
    {
        [$r, $g, $b] = $this->xyzD65ToLinP3($xyz);

        return [$this->gamP3($r), $this->gamP3($g), $this->gamP3($b)];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToP3Channels(RgbColor $rgb): array
    {
        return $this->xyzD65ToP3Channels($this->rgbToXyzD65($rgb));
    }

    public function p3ChannelsToRgb(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->xyzD65ToRgb($this->p3ToXyzD65($r, $g, $b), $opacity);
    }

    public function linA98ToXyzD65(float $r, float $g, float $b): XyzColor
    {
        [$x, $y, $z] = $this->multiply(self::M_LIN_A98_TO_XYZ, [$r, $g, $b]);

        return new XyzColor(x: $x, y: $y, z: $z);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToLinA98(XyzColor $xyz): array
    {
        return $this->multiply(self::M_XYZ_TO_LIN_A98, [$xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0]);
    }

    public function a98ToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->linA98ToXyzD65($this->linA98($r), $this->linA98($g), $this->linA98($b));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToA98Channels(XyzColor $xyz): array
    {
        [$r, $g, $b] = $this->xyzD65ToLinA98($xyz);

        return [$this->gamA98($r), $this->gamA98($g), $this->gamA98($b)];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToA98Channels(RgbColor $rgb): array
    {
        return $this->xyzD65ToA98Channels($this->rgbToXyzD65($rgb));
    }

    public function a98ChannelsToRgb(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->xyzD65ToRgb($this->a98ToXyzD65($r, $g, $b), $opacity);
    }

    public function linProphotoToXyzD50(float $r, float $g, float $b): XyzColor
    {
        [$x, $y, $z] = $this->multiply(self::M_LIN_PROPHOTO_TO_XYZ_D50, [$r, $g, $b]);

        return new XyzColor(x: $x, y: $y, z: $z);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD50ToLinProphoto(XyzColor $xyz): array
    {
        return $this->multiply(self::M_XYZ_D50_TO_LIN_PROPHOTO, [$xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0]);
    }

    public function prophotoToXyzD50(float $r, float $g, float $b): XyzColor
    {
        return $this->linProphotoToXyzD50($this->linProphoto($r), $this->linProphoto($g), $this->linProphoto($b));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD50ToProphotoChannels(XyzColor $xyz): array
    {
        [$r, $g, $b] = $this->xyzD50ToLinProphoto($xyz);

        return [$this->gamProphoto($r), $this->gamProphoto($g), $this->gamProphoto($b)];
    }

    public function prophotoToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->xyzD50ToXyzD65($this->prophotoToXyzD50($r, $g, $b));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToProphotoChannels(RgbColor $rgb): array
    {
        return $this->xyzD50ToProphotoChannels($this->rgbToXyzD50($rgb));
    }

    public function prophotoChannelsToRgb(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->xyzD65ToRgb($this->prophotoToXyzD65($r, $g, $b), $opacity);
    }

    public function linRec2020ToXyzD65(float $r, float $g, float $b): XyzColor
    {
        [$x, $y, $z] = $this->multiply(self::M_LIN_REC2020_TO_XYZ, [$r, $g, $b]);

        return new XyzColor(x: $x, y: $y, z: $z);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToLinRec2020(XyzColor $xyz): array
    {
        return $this->multiply(self::M_XYZ_TO_LIN_REC2020, [$xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0]);
    }

    public function rec2020ToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->linRec2020ToXyzD65($this->linRec2020($r), $this->linRec2020($g), $this->linRec2020($b));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToRec2020Channels(XyzColor $xyz): array
    {
        [$r, $g, $b] = $this->xyzD65ToLinRec2020($xyz);

        return [$this->gamRec2020($r), $this->gamRec2020($g), $this->gamRec2020($b)];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToRec2020Channels(RgbColor $rgb): array
    {
        return $this->xyzD65ToRec2020Channels($this->rgbToXyzD65($rgb));
    }

    public function rec2020ChannelsToRgb(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->xyzD65ToRgb($this->rec2020ToXyzD65($r, $g, $b), $opacity);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function d65ToD50(float $x, float $y, float $z): array
    {
        return $this->multiply(self::M_D65_TO_D50, [$x, $y, $z]);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function d50ToD65(float $x, float $y, float $z): array
    {
        return $this->multiply(self::M_D50_TO_D65, [$x, $y, $z]);
    }

    public function xyzD65ToXyzD50(XyzColor $xyz): XyzColor
    {
        [$x, $y, $z] = $this->d65ToD50($xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0);

        return new XyzColor($x, $y, $z);
    }

    public function xyzD50ToXyzD65(XyzColor $xyz): XyzColor
    {
        [$x, $y, $z] = $this->d50ToD65($xyz->x ?? 0.0, $xyz->y ?? 0.0, $xyz->z ?? 0.0);

        return new XyzColor($x, $y, $z);
    }

    public function labF(float $value): float
    {
        if ($value > self::LAB_EPSILON) {
            return $value ** (1.0 / 3.0);
        }

        return ((self::LAB_KAPPA * $value) + self::LAB_DELTA) / self::LAB_SCALE;
    }

    public function labToXyzD50(float $l, float $a, float $b): XyzColor
    {
        $fy = ($l + self::LAB_DELTA) / self::LAB_SCALE;
        $fx = $fy + ($a / self::LAB_A_FACTOR);
        $fz = $fy - ($b / self::LAB_B_FACTOR);

        $xr = $fx ** 3.0 > self::LAB_EPSILON ? $fx ** 3.0 : ((self::LAB_SCALE * $fx) - self::LAB_DELTA) / self::LAB_KAPPA;
        $yr = $l > self::LAB_KAPPA * self::LAB_EPSILON ? (($l + self::LAB_DELTA) / self::LAB_SCALE) ** 3.0 : $l / self::LAB_KAPPA;
        $zr = $fz ** 3.0 > self::LAB_EPSILON ? $fz ** 3.0 : ((self::LAB_SCALE * $fz) - self::LAB_DELTA) / self::LAB_KAPPA;

        return new XyzColor(x: $xr * self::D50_WHITE_X, y: $yr, z: $zr * self::D50_WHITE_Z);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD50ToLabChannels(XyzColor $xyz): array
    {
        $x = ($xyz->x ?? 0.0) / self::D50_WHITE_X;
        $y = ($xyz->y ?? 0.0) / self::D50_WHITE_Y;
        $z = ($xyz->z ?? 0.0) / self::D50_WHITE_Z;

        $fx = $this->labF($x);
        $fy = $this->labF($y);
        $fz = $this->labF($z);

        return [
            (self::LAB_SCALE * $fy) - self::LAB_DELTA,
            self::LAB_A_FACTOR * ($fx - $fy),
            self::LAB_B_FACTOR * ($fy - $fz),
        ];
    }

    public function xyzD50ToLab(XyzColor $xyz, float $alpha = 1.0): LabColor
    {
        [$l, $a, $b] = $this->xyzD50ToLabChannels($xyz);

        return new LabColor(l: $l, a: $a, b: $b, alpha: $alpha);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD50ToLchChannels(XyzColor $xyz): array
    {
        [$l, $a, $b] = $this->xyzD50ToLabChannels($xyz);

        [$c, $h] = $this->cartesianToPolar($a, $b);

        if ($c < self::ACHROMATIC_CHROMA_EPSILON) {
            $h = 0.0;
        }

        return [$l, $c, $h];
    }

    public function xyzD50ToLch(XyzColor $xyz): LchColor
    {
        [$l, $c, $h] = $this->xyzD50ToLchChannels($xyz);

        return new LchColor(l: $l, c: $c, h: $h);
    }

    public function lchToXyzD50(float $l, float $c, float $h): XyzColor
    {
        [$a, $b] = $this->polarToCartesian($c, $h);

        return $this->labToXyzD50($l, $a, $b);
    }

    public function labToXyzD65(float $l, float $a, float $b): XyzColor
    {
        return $this->xyzD50ToXyzD65($this->labToXyzD50($l, $a, $b));
    }

    public function lchToXyzD65(float $l, float $c, float $h): XyzColor
    {
        [$a, $b] = $this->polarToCartesian($c, $h);

        return $this->labToXyzD65($l, $a, $b);
    }

    public function labChannelsToRgb(float $l, float $a, float $b, float $opacity): RgbColor
    {
        return $this->xyzD50ToRgb($this->labToXyzD50($l, $a, $b), $opacity);
    }

    public function lchChannelsToRgb(float $l, float $c, float $h, float $opacity): RgbColor
    {
        [$a, $b] = $this->polarToCartesian($c, $h);

        return $this->labChannelsToRgb($l, $a, $b, $opacity);
    }

    public function rgbToLab(RgbColor $rgb): LabColor
    {
        return $this->xyzD50ToLab($this->rgbToXyzD50($rgb), $rgb->a);
    }

    public function labToRgb(LabColor $lab): RgbColor
    {
        return $this->labChannelsToRgb($lab->lValue(), $lab->aValue(), $lab->bValue(), $lab->alpha);
    }

    public function rgbToLch(RgbColor $rgb): LchColor
    {
        $lch = $this->xyzD50ToLch($this->rgbToXyzD50($rgb));

        if ($this->isAchromaticRgb($rgb)) {
            return new LchColor(l: $lch->l, c: 0.0, h: $lch->h);
        }

        return $lch;
    }

    public function lchToRgb(LchColor $lch): RgbColor
    {
        return $this->lchChannelsToRgb($lch->l ?? 0.0, $lch->c ?? 0.0, $lch->h ?? 0.0, 1.0);
    }

    public function oklabToXyzD65(float $l, float $a, float $b): XyzColor
    {
        [$lPrime, $mPrime, $sPrime] = $this->multiply(self::M_OKLAB_TO_LMS, [$l, $a, $b]);

        [$x, $y, $z] = $this->multiply(self::M_LMS_TO_XYZ, [
            $lPrime ** 3.0,
            $mPrime ** 3.0,
            $sPrime ** 3.0,
        ]);

        return new XyzColor(x: $x, y: $y, z: $z);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToOklabChannels(XyzColor $xyz): array
    {
        [$lLms, $mLms, $sLms] = $this->multiply(self::M_XYZ_TO_LMS, [
            $xyz->x ?? 0.0,
            $xyz->y ?? 0.0,
            $xyz->z ?? 0.0,
        ]);

        return $this->lmsToOklab($this->cubeRoot($lLms), $this->cubeRoot($mLms), $this->cubeRoot($sLms));
    }

    public function xyzD65ToOklab(XyzColor $xyz, float $alpha = 1.0): OklabColor
    {
        [$l, $a, $b] = $this->xyzD65ToOklabChannels($xyz);

        return new OklabColor(l: $l * 100.0, a: $a, b: $b, alpha: $alpha);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToOklchChannels(XyzColor $xyz): array
    {
        [$l, $a, $b] = $this->xyzD65ToOklabChannels($xyz);

        [$c, $h] = $this->cartesianToPolar($a, $b);

        return [$l, $c, $h];
    }

    public function xyzD65ToOklch(XyzColor $xyz, float $alpha = 1.0): OklchColor
    {
        [$l, $a, $b] = $this->xyzD65ToOklabChannels($xyz);

        return $this->oklabComponentsToOklch($l, $a, $b, $alpha);
    }

    public function oklchToXyzD65(float $l, float $c, float $h): XyzColor
    {
        [$a, $b] = $this->polarToCartesian($c, $h);

        return $this->oklabToXyzD65($l, $a, $b);
    }

    public function oklabChannelsToRgb(float $l, float $a, float $b, float $opacity): RgbColor
    {
        [$rLinear, $gLinear, $bLinear] = $this->oklabToLinSrgbChannels($l, $a, $b);

        return new RgbColor(
            r: $this->gamSrgb($rLinear),
            g: $this->gamSrgb($gLinear),
            b: $this->gamSrgb($bLinear),
            a: $opacity,
        );
    }

    public function oklchChannelsToRgb(float $l, float $c, float $h, float $opacity): RgbColor
    {
        [$a, $b] = $this->polarToCartesian($c, $h);

        return $this->oklabChannelsToRgb($l, $a, $b, $opacity);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToOklabChannels(RgbColor $rgb): array
    {
        $r = $this->linSrgb($rgb->r ?? 0.0);
        $g = $this->linSrgb($rgb->g ?? 0.0);
        $b = $this->linSrgb($rgb->b ?? 0.0);

        return $this->linSrgbToOklabChannels($r, $g, $b);
    }

    public function rgbToOklab(RgbColor $rgb): OklabColor
    {
        [$l, $a, $b] = $this->rgbToOklabChannels($rgb);

        return new OklabColor(l: $l * 100.0, a: $a, b: $b, alpha: $rgb->a);
    }

    public function rgbToOklch(RgbColor $rgb): OklchColor
    {
        [$l, $a, $b] = $this->rgbToOklabChannels($rgb);

        return $this->oklabComponentsToOklch($l, $a, $b, $rgb->a);
    }

    public function oklabToRgb(OklabColor $oklab): RgbColor
    {
        return $this->oklabChannelsToRgb(($oklab->l ?? 0.0) / 100.0, $oklab->a ?? 0.0, $oklab->b ?? 0.0, $oklab->alpha);
    }

    public function oklchToRgb(OklchColor $oklch): RgbColor
    {
        $l = ($oklch->l ?? 0.0) / 100.0;
        $c = max(0.0, $oklch->c ?? 0.0);

        [$a, $b] = $this->polarToCartesian($c, $oklch->h ?? 0.0);

        return $this->oklabChannelsToRgb($l, $a, $b, $oklch->a);
    }

    public function oklchToLch(OklchColor $oklch): LchColor
    {
        $l = ($oklch->l ?? 0.0) / 100.0;

        [$a, $b] = $this->polarToCartesian($oklch->c ?? 0.0, $oklch->h ?? 0.0);

        return $this->xyzD50ToLch($this->xyzD65ToXyzD50($this->oklabToXyzD65($l, $a, $b)));
    }

    public function interpolateHue(float $h1, float $h2, float $p): float
    {
        $delta = fmod($h2 - $h1 + 540.0, 360.0) - 180.0;
        $mixed = $h1 + ((1.0 - $p) * $delta);

        return $this->normalizeHue($mixed);
    }

    public function trimFloat(float $value, int $precision = 6): string
    {
        $rounded = round($value, $precision);
        $text    = sprintf('%.' . $precision . 'f', $rounded);
        $text    = rtrim($text, '0');

        return rtrim($text, '.');
    }

    public function mixChannel(?float $a, ?float $b, float $p): float
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
        $r = $rgb->r ?? 0.0;
        $g = $rgb->g ?? 0.0;
        $b = $rgb->b ?? 0.0;

        return abs($r - $g) <= self::ACHROMATIC_CHANNEL_EPSILON && abs($g - $b) <= self::ACHROMATIC_CHANNEL_EPSILON;
    }

    public function calculateDeltaE(RgbColor $rgb1, RgbColor $rgb2): float
    {
        [$l1, $a1, $b1] = $this->rgbToOklabChannels($rgb1);
        [$l2, $a2, $b2] = $this->rgbToOklabChannels($rgb2);

        $deltaL = $l1 - $l2;
        $deltaA = $a1 - $a2;
        $deltaB = $b1 - $b2;

        return sqrt(($deltaL * $deltaL) + ($deltaA * $deltaA) + ($deltaB * $deltaB));
    }

    /** @deprecated Use linSrgb(). */
    public function srgbToLinear(float $value): float
    {
        return $this->linSrgb($value);
    }

    /** @deprecated Use linSrgb(). */
    public function srgbToLinearUnclamped(?float $value): float
    {
        return $this->linSrgb($value);
    }

    /** @deprecated Use gamSrgb(). */
    public function linearToSrgb(float $value): float
    {
        return $this->gamSrgb($value);
    }

    /** @deprecated Use gamSrgb(). */
    public function linearToSrgbUnclamped(float $value): float
    {
        return $this->gamSrgb($value);
    }

    /** @deprecated Use oklchToRgb(). */
    public function oklchToSrgb(OklchColor $oklch): RgbColor
    {
        return $this->oklchToRgb($oklch);
    }

    /** @deprecated Use oklchToRgb(). */
    public function oklchToSrgbUnclamped(OklchColor $oklch): RgbColor
    {
        return $this->oklchToRgb($oklch);
    }

    /**
     * @deprecated Use oklabChannelsToRgb() (returns RgbColor instead of an array).
     * @return array{0: float, 1: float, 2: float}
     */
    public function oklabToSrgb(float $l, float $a, float $b): array
    {
        return $this->oklabChannelsToRgbComponents($l, $a, $b);
    }

    /**
     * @deprecated Use oklabChannelsToRgb() (returns RgbColor instead of an array).
     * @return array{0: float, 1: float, 2: float}
     */
    public function oklabToSrgbUnclamped(float $l, float $a, float $b): array
    {
        return $this->oklabChannelsToRgbComponents($l, $a, $b);
    }

    /**
     * @deprecated Use labChannelsToRgb() (returns RgbColor instead of an array).
     * @return array{0: float, 1: float, 2: float}
     */
    public function labToSrgb(float $l, float $a, float $b): array
    {
        $rgb = $this->labChannelsToRgb($l, $a, $b, 1.0);

        return [$rgb->rValue(), $rgb->gValue(), $rgb->bValue()];
    }

    /** @deprecated Use labToRgb(). */
    public function labToRgbColor(LabColor $lab): RgbColor
    {
        return $this->labToRgb($lab);
    }

    /** @deprecated Use srgbToXyzD65(). */
    public function srgbChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->srgbToXyzD65($r, $g, $b);
    }

    /** @deprecated Use linSrgbToXyzD65(). */
    public function linearSrgbChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->linSrgbToXyzD65($r, $g, $b);
    }

    /** @deprecated Use srgbChannelsToRgb(). */
    public function srgbChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->srgbChannelsToRgb($r, $g, $b, $opacity);
    }

    /** @deprecated Use linSrgbChannelsToRgb(). */
    public function linearSrgbChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->linSrgbChannelsToRgb($r, $g, $b, $opacity);
    }

    /** @deprecated Use p3ChannelsToRgb(). */
    public function displayP3ChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->p3ChannelsToRgb($r, $g, $b, $opacity);
    }

    /** @deprecated Use xyzD65ToRgb() + linP3ToXyzD65(). */
    public function linearDisplayP3ChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->xyzD65ToRgb($this->linP3ToXyzD65($r, $g, $b), $opacity);
    }

    /** @deprecated Use linP3ToXyzD65(). */
    public function linearDisplayP3ChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->linP3ToXyzD65($r, $g, $b);
    }

    /** @deprecated Use p3ToXyzD65(). */
    public function displayP3ChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->p3ToXyzD65($r, $g, $b);
    }

    /** @deprecated Use xyzD65ToRgb(). */
    public function xyzD65ToSrgba(XyzColor $xyz, float $opacity): RgbColor
    {
        return $this->xyzD65ToRgb($xyz, $opacity);
    }

    /** @deprecated Use xyzD50ToRgb(). */
    public function xyzD50ToSrgba(XyzColor $xyz, float $opacity): RgbColor
    {
        return $this->xyzD50ToRgb($xyz, $opacity);
    }

    /** @deprecated Use a98ToXyzD65(). */
    public function a98RgbChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->a98ToXyzD65($r, $g, $b);
    }

    /** @deprecated Use a98ChannelsToRgb(). */
    public function a98RgbChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->a98ChannelsToRgb($r, $g, $b, $opacity);
    }

    /** @deprecated Use prophotoToXyzD65(). */
    public function prophotoRgbChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->prophotoToXyzD65($r, $g, $b);
    }

    /** @deprecated Use prophotoChannelsToRgb(). */
    public function prophotoRgbChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->prophotoChannelsToRgb($r, $g, $b, $opacity);
    }

    /** @deprecated Use rec2020ToXyzD65(). */
    public function rec2020ChannelsToXyzD65(float $r, float $g, float $b): XyzColor
    {
        return $this->rec2020ToXyzD65($r, $g, $b);
    }

    /** @deprecated Use rec2020ChannelsToRgb(). */
    public function rec2020ChannelsToSrgba(float $r, float $g, float $b, float $opacity): RgbColor
    {
        return $this->rec2020ChannelsToRgb($r, $g, $b, $opacity);
    }

    /**
     * @deprecated Use xyzD65ToLinP3().
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToLinearDisplayP3(XyzColor $xyz): array
    {
        return $this->xyzD65ToLinP3($xyz);
    }

    /**
     * @deprecated Use rgbToP3Channels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToDisplayP3(RgbColor $rgb): array
    {
        return $this->rgbToP3Channels($rgb);
    }

    /**
     * @deprecated Use xyzD65ToP3Channels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToDisplayP3(XyzColor $xyz): array
    {
        return $this->xyzD65ToP3Channels($xyz);
    }

    /**
     * @deprecated Use rgbToA98Channels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToA98Rgb(RgbColor $rgb): array
    {
        return $this->rgbToA98Channels($rgb);
    }

    /**
     * @deprecated Use xyzD65ToA98Channels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToA98Rgb(XyzColor $xyz): array
    {
        return $this->xyzD65ToA98Channels($xyz);
    }

    /**
     * @deprecated Use rgbToProphotoChannels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToProphotoRgb(RgbColor $rgb): array
    {
        return $this->rgbToProphotoChannels($rgb);
    }

    /**
     * @deprecated Use xyzD50ToProphotoChannels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD50ToProphotoRgb(XyzColor $xyz): array
    {
        return $this->xyzD50ToProphotoChannels($xyz);
    }

    /**
     * @deprecated Use rgbToRec2020Channels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function rgbToRec2020(RgbColor $rgb): array
    {
        return $this->rgbToRec2020Channels($rgb);
    }

    /**
     * @deprecated Use xyzD65ToRec2020Channels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToRec2020(XyzColor $xyz): array
    {
        return $this->xyzD65ToRec2020Channels($xyz);
    }

    /** @deprecated Use labChannelsToRgb(). */
    public function labChannelsToSrgba(float $l, float $a, float $b, float $opacity): RgbColor
    {
        return $this->labChannelsToRgb($l, $a, $b, $opacity);
    }

    /** @deprecated Use labToXyzD65(). */
    public function labChannelsToXyzD65(float $l, float $a, float $b): XyzColor
    {
        return $this->labToXyzD65($l, $a, $b);
    }

    /** @deprecated Use lchChannelsToRgb(). */
    public function lchChannelsToSrgba(float $l, float $c, float $h, float $opacity): RgbColor
    {
        return $this->lchChannelsToRgb($l, $c, $h, $opacity);
    }

    /** @deprecated Use lchToXyzD65(). */
    public function lchChannelsToXyzD65(float $l, float $c, float $h): XyzColor
    {
        return $this->lchToXyzD65($l, $c, $h);
    }

    /**
     * @deprecated Use xyzD50ToLabChannels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzToLabD50(XyzColor $xyz): array
    {
        return $this->xyzD50ToLabChannels($xyz);
    }

    /**
     * @deprecated Use xyzD50ToLchChannels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzToLchD50(XyzColor $xyz): array
    {
        return $this->xyzD50ToLchChannels($xyz);
    }

    /** @deprecated Use xyzD50ToLab(). */
    public function xyzD50ToLabColor(XyzColor $xyz, float $alpha = 1.0): LabColor
    {
        return $this->xyzD50ToLab($xyz, $alpha);
    }

    /**
     * @deprecated Use d65ToD50() (same values, new name per spec terminology).
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD65ToD50(float $x, float $y, float $z): array
    {
        return $this->d65ToD50($x, $y, $z);
    }

    /**
     * @deprecated Use d50ToD65() (same values, new name per spec terminology).
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzD50ToD65(float $x, float $y, float $z): array
    {
        return $this->d50ToD65($x, $y, $z);
    }

    /** @deprecated Use xyzD65ToOklab(). */
    public function xyzD65ToOklabColor(XyzColor $xyz, float $alpha = 1.0): OklabColor
    {
        return $this->xyzD65ToOklab($xyz, $alpha);
    }

    /** @deprecated Use oklabToXyzD65(). */
    public function oklabChannelsToXyzD65(float $l, float $a, float $b): XyzColor
    {
        return $this->oklabToXyzD65($l, $a, $b);
    }

    /** @deprecated Use oklabChannelsToRgb(). */
    public function oklabChannelsToSrgba(float $l, float $a, float $b, float $opacity): RgbColor
    {
        return $this->oklabChannelsToRgb($l, $a, $b, $opacity);
    }

    /** @deprecated Use oklchChannelsToRgb(). */
    public function oklchChannelsToSrgba(float $l, float $c, float $h, float $opacity): RgbColor
    {
        return $this->oklchChannelsToRgb($l, $c, $h, $opacity);
    }

    /** @deprecated Use oklchToXyzD65(). */
    public function oklchChannelsToXyzD65(float $l, float $c, float $h): XyzColor
    {
        return $this->oklchToXyzD65($l, $c, $h);
    }

    /**
     * @deprecated Use xyzD65ToOklabChannels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzToOklabD65(XyzColor $xyz): array
    {
        return $this->xyzD65ToOklabChannels($xyz);
    }

    /**
     * @deprecated Use xyzD65ToOklchChannels().
     * @return array{0: float, 1: float, 2: float}
     */
    public function xyzToOklchD65(XyzColor $xyz): array
    {
        return $this->xyzD65ToOklchChannels($xyz);
    }

    /**
     * @deprecated Use rgbToOklch(). RgbColor channels are always in the 0..1 range.
     */
    public function normalizedChannelsToOklch(RgbColor $rgb): OklchColor
    {
        return $this->rgbToOklch($rgb);
    }

    /**
     * @deprecated Use rgbToOklch(). The $clampChannels parameter no longer
     * affects the result (both branches were already identical in the original class).
     */
    public function normalizedSrgbToOklch(RgbColor $rgb, bool $clampChannels = false): OklchColor
    {
        return $this->rgbToOklch($rgb);
    }

    /**
     * @deprecated Use rgbToOklch().
     */
    public function normalizedRgbToOklch(RgbColor $rgb, bool $clampChannels): OklchColor
    {
        return $this->rgbToOklch($rgb);
    }

    /**
     * @param array{array{float,float,float},array{float,float,float},array{float,float,float}} $m
     * @param array{float,float,float} $v
     * @return array{float,float,float}
     */
    private function multiply(array $m, array $v): array
    {
        return [
            ($m[0][0] * $v[0]) + ($m[0][1] * $v[1]) + ($m[0][2] * $v[2]),
            ($m[1][0] * $v[0]) + ($m[1][1] * $v[1]) + ($m[1][2] * $v[2]),
            ($m[2][0] * $v[0]) + ($m[2][1] * $v[1]) + ($m[2][2] * $v[2]),
        ];
    }

    private function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0.0) {
            $t += 1.0;
        }

        if ($t > 1.0) {
            $t -= 1.0;
        }

        if ($t < 1.0 / 6.0) {
            return $p + (($q - $p) * 6.0 * $t);
        }

        if ($t < 0.5) {
            return $q;
        }

        if ($t < 2.0 / 3.0) {
            return $p + (($q - $p) * ((2.0 / 3.0) - $t) * 6.0);
        }

        return $p;
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function linSrgbToOklabChannels(float $r, float $g, float $b): array
    {
        [$lLms, $mLms, $sLms] = $this->multiply(self::M_LIN_SRGB_TO_LMS, [$r, $g, $b]);

        return $this->lmsToOklab($this->cubeRoot($lLms), $this->cubeRoot($mLms), $this->cubeRoot($sLms));
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function oklabToLinSrgbChannels(float $l, float $a, float $b): array
    {
        [$lPrime, $mPrime, $sPrime] = $this->multiply(self::M_OKLAB_TO_LMS, [$l, $a, $b]);

        return $this->multiply(self::M_LMS_TO_LIN_SRGB, [
            $lPrime * $lPrime * $lPrime,
            $mPrime * $mPrime * $mPrime,
            $sPrime * $sPrime * $sPrime,
        ]);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function oklabChannelsToRgbComponents(float $l, float $a, float $b): array
    {
        $rgb = $this->oklabChannelsToRgb($l, $a, $b, 1.0);

        return [$rgb->rValue(), $rgb->gValue(), $rgb->bValue()];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function lmsToOklab(float $l, float $m, float $s): array
    {
        return $this->multiply(self::M_LMS_TO_OKLAB, [$l, $m, $s]);
    }

    private function oklabComponentsToOklch(float $l, float $a, float $b, float $alpha): OklchColor
    {
        [$chroma, $hue] = $this->cartesianToPolar($a, $b);

        return new OklchColor(
            l: $l * 100.0,
            c: max(0.0, $chroma),
            h: $chroma < self::ACHROMATIC_CHROMA_EPSILON ? null : $this->normalizeHue($hue),
            a: $alpha,
        );
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function polarToCartesian(float $radius, float $hueDegrees): array
    {
        $hueRadians = ($hueDegrees * M_PI) / 180.0;

        return [$radius * cos($hueRadians), $radius * sin($hueRadians)];
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function cartesianToPolar(float $a, float $b): array
    {
        $hue = (atan2($b, $a) * 180.0) / M_PI;

        if ($hue < 0.0) {
            $hue += 360.0;
        }

        return [sqrt(($a * $a) + ($b * $b)), $hue];
    }
}
