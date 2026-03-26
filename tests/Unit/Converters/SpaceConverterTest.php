<?php

declare(strict_types=1);

use Bugo\Iris\Converters\NormalizedRgbChannels;
use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\LchColor;
use Bugo\Iris\Spaces\OklabColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;
use Bugo\Iris\Spaces\XyzColor;

describe('SpaceConverter', function (): void {
    beforeEach(function (): void {
        $this->converter = new SpaceConverter();
    });

    describe('hslToRgb', function (): void {
        it('converts red hsl(0, 100%, 50%) to rgb(1,0,0)', function (): void {
            [$r, $g, $b] = $this->converter->hslToRgb(0.0, 1.0, 0.5);

            expect(round($r, 4))->toBe(1.0)
                ->and(round($g, 4))->toBe(0.0)
                ->and(round($b, 4))->toBe(0.0);
        });

        it('converts green hsl(120, 100%, 50%) to rgb(0,1,0)', function (): void {
            [$r, $g, $b] = $this->converter->hslToRgb(120.0, 1.0, 0.5);

            expect(round($r, 4))->toBe(0.0)
                ->and(round($g, 4))->toBe(1.0)
                ->and(round($b, 4))->toBe(0.0);
        });

        it('converts white hsl(0, 0%, 100%) to rgb(1,1,1)', function (): void {
            [$r, $g, $b] = $this->converter->hslToRgb(0.0, 0.0, 1.0);

            expect(round($r, 4))->toBe(1.0)
                ->and(round($g, 4))->toBe(1.0)
                ->and(round($b, 4))->toBe(1.0);
        });

        it('converts black hsl(0, 0%, 0%) to rgb(0,0,0)', function (): void {
            [$r, $g, $b] = $this->converter->hslToRgb(0.0, 0.0, 0.0);

            expect(round($r, 4))->toBe(0.0)
                ->and(round($g, 4))->toBe(0.0)
                ->and(round($b, 4))->toBe(0.0);
        });

        it('converts green(128) hsl(120, 100%, 25.098%) approximately', function (): void {
            // rgb(0,128,0) => normalized (0, 0.502, 0) => hsl(120, 100%, 25.1%)
            [$r, $g, $b] = $this->converter->hslToRgb(120.0, 1.0, 128.0 / 510.0);

            expect(round($r, 2))->toBe(0.0)
                ->and($g)->toBeGreaterThan(0.49)
                ->and($g)->toBeLessThan(0.51)
                ->and(round($b, 2))->toBe(0.0);
        });
    });

    describe('rgbToOklch', function (): void {
        it('converts rgb(255,0,0) to oklch with expected lightness, chroma, hue', function (): void {
            $rgb   = new RgbColor(255.0, 0.0, 0.0, 1.0);
            $oklch = $this->converter->rgbToOklch($rgb);

            // Expected: l≈62.79, c≈0.2576, h≈29.23
            expect(round($oklch->l, 2))->toBeGreaterThan(62.0)
                ->and(round($oklch->l, 2))->toBeLessThan(64.0)
                ->and(round($oklch->c, 3))->toBeGreaterThan(0.25)
                ->and(round($oklch->c, 3))->toBeLessThan(0.27)
                ->and(round($oklch->h, 1))->toBeGreaterThan(28.0)
                ->and(round($oklch->h, 1))->toBeLessThan(31.0);
        });

        it('converts rgb(0,255,0) to oklch with high lightness and green hue', function (): void {
            $rgb   = new RgbColor(0.0, 255.0, 0.0, 1.0);
            $oklch = $this->converter->rgbToOklch($rgb);

            expect($oklch->l)->toBeGreaterThan(80.0)
                ->and($oklch->h)->toBeGreaterThan(100.0)
                ->and($oklch->h)->toBeLessThan(150.0);
        });

        it('converts rgb(0,0,0) to oklch with near-zero lightness', function (): void {
            $rgb   = new RgbColor(0.0, 0.0, 0.0, 1.0);
            $oklch = $this->converter->rgbToOklch($rgb);

            expect(round($oklch->l, 4))->toBe(0.0);
        });

        it('converts rgb(255,255,255) to oklch with 100 lightness', function (): void {
            $rgb   = new RgbColor(255.0, 255.0, 255.0, 1.0);
            $oklch = $this->converter->rgbToOklch($rgb);

            expect(round($oklch->l, 2))->toBe(100.0);
        });
    });

    describe('oklchToSrgb', function (): void {
        it('round-trip rgb(255,0,0) → oklch → rgb stays close to original', function (): void {
            $original = new RgbColor(255.0, 0.0, 0.0, 1.0);
            $oklch    = $this->converter->rgbToOklch($original);

            // oklchToSrgb returns 0-1 scale
            $restored = $this->converter->oklchToSrgb($oklch);

            expect(round($restored->r, 2))->toBe(1.0)
                ->and(round($restored->g, 2))->toBe(0.0)
                ->and(round($restored->b, 2))->toBe(0.0);
        });

        it('round-trip rgb(0,128,0) → oklch → rgb stays close to original', function (): void {
            $original = new RgbColor(0.0, 128.0, 0.0, 1.0);
            $oklch    = $this->converter->rgbToOklch($original);
            $restored = $this->converter->oklchToSrgb($oklch);

            expect(round($restored->r, 2))->toBe(0.0)
                ->and($restored->g)->toBeGreaterThan(0.49)
                ->and($restored->g)->toBeLessThan(0.52)
                ->and(round($restored->b, 2))->toBe(0.0);
        });
    });

    describe('utility methods', function (): void {
        it('clamp keeps value within [0, max]', function (): void {
            expect($this->converter->clamp(-1.0, 1.0))->toBe(0.0)
                ->and($this->converter->clamp(2.0, 1.0))->toBe(1.0)
                ->and($this->converter->clamp(0.5, 1.0))->toBe(0.5);
        });

        it('normalizeHue wraps negative hue', function (): void {
            expect($this->converter->normalizeHue(-30.0))->toBe(330.0);
        });

        it('normalizeHue wraps hue above 360', function (): void {
            expect($this->converter->normalizeHue(400.0))->toBe(40.0);
        });

        it('mixChannel interpolates between two values', function (): void {
            // mixChannel(a, b, p) = a*p + b*(1-p)
            expect($this->converter->mixChannel(0.0, 100.0, 0.5))->toBe(50.0)
                ->and($this->converter->mixChannel(0.0, 100.0, 1.0))->toBe(0.0)
                ->and($this->converter->mixChannel(0.0, 100.0, 0.0))->toBe(100.0);
        });
    });

    describe('roundFloat()', function (): void {
        it('rounds to 6 decimal places by default', function (): void {
            expect($this->converter->roundFloat(1.1234567))->toBeCloseTo(1.123457, 0.000001);
        });

        it('rounds to specified precision', function (): void {
            expect($this->converter->roundFloat(1.567, 2))->toBeCloseTo(1.57, 0.001);
        });

        it('rounds exactly representable values unchanged', function (): void {
            expect($this->converter->roundFloat(1.5, 6))->toBe(1.5);
        });
    });

    describe('cubeRoot()', function (): void {
        it('returns 0 for 0', function (): void {
            expect($this->converter->cubeRoot(0.0))->toBe(0.0);
        });

        it('returns 1 for 1', function (): void {
            expect($this->converter->cubeRoot(1.0))->toBeCloseTo(1.0, 0.000001);
        });

        it('returns 2 for 8', function (): void {
            expect($this->converter->cubeRoot(8.0))->toBeCloseTo(2.0, 0.000001);
        });

        it('returns negative cube root for negative values', function (): void {
            expect($this->converter->cubeRoot(-8.0))->toBeCloseTo(-2.0, 0.000001);
        });
    });

    describe('srgbToLinear() / linearToSrgb()', function (): void {
        it('linearizes black (0) to 0', function (): void {
            expect($this->converter->srgbToLinear(0.0))->toBe(0.0);
        });

        it('linearizes white (1) to 1', function (): void {
            expect($this->converter->srgbToLinear(1.0))->toBe(1.0);
        });

        it('linearizes mid-gray correctly', function (): void {
            // sRGB 0.5 ≈ linear 0.2140
            expect($this->converter->srgbToLinear(0.5))->toBeCloseTo(0.2140, 0.001);
        });

        it('clamps values above 1', function (): void {
            expect($this->converter->srgbToLinear(1.5))->toBe(1.0);
        });

        it('clamps negative values to 0', function (): void {
            expect($this->converter->srgbToLinear(-0.1))->toBe(0.0);
        });

        it('linearToSrgb is inverse of srgbToLinear for 0', function (): void {
            $linear = $this->converter->srgbToLinear(0.0);

            expect($this->converter->linearToSrgb($linear))->toBe(0.0);
        });

        it('linearToSrgb is inverse of srgbToLinear for mid-gray', function (): void {
            $original = 0.5;

            $linear = $this->converter->srgbToLinear($original);

            expect($this->converter->linearToSrgb($linear))->toBeCloseTo($original, 0.0001);
        });
    });

    describe('hwbToRgb()', function (): void {
        it('converts hwb(0, 0, 0) to pure red', function (): void {
            [$r, $g, $b] = $this->converter->hwbToRgb(0.0, 0.0, 0.0);

            expect($r)->toBeCloseTo(1.0, 0.00001)
                ->and($g)->toBeCloseTo(0.0, 0.00001)
                ->and($b)->toBeCloseTo(0.0, 0.00001);
        });

        it('converts hwb(0, 1, 0) to white', function (): void {
            [$r, $g, $b] = $this->converter->hwbToRgb(0.0, 1.0, 0.0);

            expect($r)->toBeCloseTo(1.0, 0.00001)
                ->and($g)->toBeCloseTo(1.0, 0.00001)
                ->and($b)->toBeCloseTo(1.0, 0.00001);
        });

        it('converts hwb(0, 0, 1) to black', function (): void {
            [$r, $g, $b] = $this->converter->hwbToRgb(0.0, 0.0, 1.0);

            expect($r)->toBeCloseTo(0.0, 0.00001)
                ->and($g)->toBeCloseTo(0.0, 0.00001)
                ->and($b)->toBeCloseTo(0.0, 0.00001);
        });

        it('returns gray when whiteness + blackness >= 1', function (): void {
            // 60% white + 60% black → gray = whiteness / (whiteness + blackness) = 0.5
            [$r, $g, $b] = $this->converter->hwbToRgb(0.0, 0.6, 0.6);

            expect($r)->toBeCloseTo(0.5, 0.00001)
                ->and($g)->toBeCloseTo(0.5, 0.00001)
                ->and($b)->toBeCloseTo(0.5, 0.00001);
        });
    });

    describe('oklchToSrgb() additional', function (): void {
        it('round-trip white stays white', function (): void {
            $rgb    = new RgbColor(r: 255.0, g: 255.0, b: 255.0, a: 1.0);
            $oklch  = $this->converter->rgbToOklch($rgb);
            $result = $this->converter->oklchToSrgb($oklch);

            expect($result->r)->toBeCloseTo(1.0, 0.01)
                ->and($result->g)->toBeCloseTo(1.0, 0.01)
                ->and($result->b)->toBeCloseTo(1.0, 0.01);
        });

        it('oklchToSrgb preserves alpha', function (): void {
            $oklch  = new OklchColor(l: 50.0, c: 0.1, h: 0.0, a: 0.5);
            $result = $this->converter->oklchToSrgb($oklch);

            expect($result->a)->toBe(0.5);
        });
    });

    describe('srgbToLinearUnclamped', function (): void {
        it('converts without clamping', function (): void {
            expect($this->converter->srgbToLinearUnclamped(1.5))->toBeGreaterThan(1.0)
                ->and($this->converter->srgbToLinearUnclamped(-0.1))->toBeLessThan(0.0);
        });
    });

    describe('linearToSrgbUnclamped', function (): void {
        it('converts without clamping', function (): void {
            expect($this->converter->linearToSrgbUnclamped(1.5))->toBeGreaterThan(1.0)
                ->and($this->converter->linearToSrgbUnclamped(-0.1))->toBeLessThan(0.0);
        });
    });

    describe('interpolateHue', function (): void {
        it('interpolates hues in shortest direction', function (): void {
            expect($this->converter->interpolateHue(0.0, 90.0, 0.5))->toBeCloseTo(45.0, 0.00001)
                ->and($this->converter->interpolateHue(10.0, 350.0, 0.5))->toBeCloseTo(0.0, 0.00001);
        });
    });

    describe('labF', function (): void {
        it('returns cube root for values above epsilon', function (): void {
            expect($this->converter->labF(0.1))->toBeCloseTo(0.464159, 0.000001);
        });

        it('returns linear interpolation for values below epsilon', function (): void {
            $epsilon = 216.0 / 24389.0;

            expect($this->converter->labF($epsilon / 2))->toBeCloseTo((24389.0 / 27.0 * ($epsilon / 2) + 16.0) / 116.0, 0.000001);
        });
    });

    describe('trimFloat', function (): void {
        it('trims trailing zeros', function (): void {
            expect($this->converter->trimFloat(1.500000))->toBe('1.5')
                ->and($this->converter->trimFloat(1.000000))->toBe('1');
        });
    });

    describe('scaleLinear', function (): void {
        it('scales linearly for positive delta', function (): void {
            expect($this->converter->scaleLinear(50.0, 50.0, 100.0))->toBe(75.0);
        });

        it('scales linearly for negative delta', function (): void {
            expect($this->converter->scaleLinear(50.0, -50.0, 100.0))->toBe(25.0);
        });
    });

    describe('hueFromNormalizedRgb', function (): void {
        it('returns 0 for achromatic', function (): void {
            $channels = new NormalizedRgbChannels(0.5, 0.5, 0.5, 0.5, 0.5, 0.5, 0.0);

            expect($this->converter->hueFromNormalizedRgb($channels))->toBe(0.0);
        });

        it('calculates hue when red is max', function (): void {
            $channels = new NormalizedRgbChannels(1.0, 0.5, 0.0, 1.0, 1.0, 0.0, 1.0);

            expect($this->converter->hueFromNormalizedRgb($channels))->toBeCloseTo(30.0, 0.00001);
        });

        it('calculates hue when green is max', function (): void {
            $channels = new NormalizedRgbChannels(0.0, 1.0, 0.5, 1.0, 1.0, 0.0, 1.0);

            expect($this->converter->hueFromNormalizedRgb($channels))->toBeCloseTo(150.0, 0.00001);
        });

        it('calculates hue when blue is max', function (): void {
            $channels = new NormalizedRgbChannels(0.5, 0.0, 1.0, 1.0, 1.0, 0.0, 1.0);

            expect($this->converter->hueFromNormalizedRgb($channels))->toBeCloseTo(270.0, 0.00001);
        });
    });

    describe('isAchromaticRgb', function (): void {
        it('returns true for gray colors', function (): void {
            $rgb = new RgbColor(0.5, 0.5, 0.5, 1.0);

            expect($this->converter->isAchromaticRgb($rgb))->toBeTrue();
        });

        it('returns false for colored', function (): void {
            $rgb = new RgbColor(1.0, 0.0, 0.0, 1.0);

            expect($this->converter->isAchromaticRgb($rgb))->toBeFalse();
        });
    });

    describe('linearSrgbChannelsToXyzD65', function (): void {
        it('converts linear srgb to xyz', function (): void {
            $xyz = $this->converter->linearSrgbChannelsToXyzD65(1.0, 0.0, 0.0);

            expect($xyz->x)->toBeCloseTo(0.4124, 0.0001)
                ->and($xyz->y)->toBeCloseTo(0.2126, 0.0001)
                ->and($xyz->z)->toBeCloseTo(0.0193, 0.0001);
        });
    });

    describe('linearDisplayP3ChannelsToXyzD65', function (): void {
        it('converts linear display-p3 to xyz', function (): void {
            $xyz = $this->converter->linearDisplayP3ChannelsToXyzD65(1.0, 0.0, 0.0);

            expect($xyz->x)->toBeCloseTo(0.4866, 0.0001)
                ->and($xyz->y)->toBeCloseTo(0.2290, 0.0001);
        });
    });

    describe('rgbToXyzD50', function (): void {
        it('converts rgb to xyz d50', function (): void {
            $rgb = new RgbColor(255.0, 0.0, 0.0, 1.0);
            $xyz = $this->converter->rgbToXyzD50($rgb);

            expect($xyz->x)->toBeGreaterThan(0.4);
        });
    });

    describe('oklchToSrgbUnclamped', function (): void {
        it('converts oklch to srgb without clamping', function (): void {
            $oklch = new OklchColor(50.0, 0.2, 180.0, 1.0);
            $rgb   = $this->converter->oklchToSrgbUnclamped($oklch);

            expect($rgb->a)->toBe(1.0);
        });
    });

    describe('hueToRgb', function (): void {
        it('handles t < 0 via hslToRgb', function (): void {
            [$r, $g, $b] = $this->converter->hslToRgb(0.0, 0.5, 0.5);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });

        it('handles t > 1 via hslToRgb', function (): void {
            [$r, $g, $b] = $this->converter->hslToRgb(300.0, 0.5, 0.5);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });

        it('handles t < 1/6 via hslToRgb', function (): void {
            [$r, $g, $b] = $this->converter->hslToRgb(30.0, 0.5, 0.5);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });

        it('handles t < 0.5 via hslToRgb', function (): void {
            [$r, $g, $b] = $this->converter->hslToRgb(90.0, 0.5, 0.5);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });

        it('handles t < 2/3 via hslToRgb', function (): void {
            [$r, $g, $b] = $this->converter->hslToRgb(200.0, 0.5, 0.5);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });

        it('handles t >= 2/3 via hslToRgb', function (): void {
            [$r, $g, $b] = $this->converter->hslToRgb(270.0, 0.5, 0.5);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });
    });

    describe('conversion functions', function (): void {
        it('linearToA98Rgb converts via xyzD65ToA98Rgb', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            [$r, $g, $b] = $this->converter->xyzD65ToA98Rgb($xyz);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });

        it('prophotoToLinear converts via prophotoRgbToXyzD65', function (): void {
            $xyz = $this->converter->prophotoRgbChannelsToXyzD65(0.5, 0.5, 0.5);

            expect($xyz->x)->toBeFloat();
        });

        it('rec2020ToLinear converts via rec2020RgbToXyzD65', function (): void {
            $xyz = $this->converter->rec2020ChannelsToXyzD65(0.5, 0.5, 0.5);

            expect($xyz->x)->toBeFloat();
        });

        it('linearToProphotoRgb converts via xyzD50ToProphotoRgb', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            [$r, $g, $b] = $this->converter->xyzD50ToProphotoRgb($xyz);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });

        it('linearToRec2020 converts via xyzD65ToRec2020', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            [$r, $g, $b] = $this->converter->xyzD65ToRec2020($xyz);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });

        it('xyzD65ToD50 converts via xyzToLabD50', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            [$l, $a, $b] = $this->converter->xyzToLabD50($xyz);

            expect($l)->toBeFloat()->and($a)->toBeFloat()->and($b)->toBeFloat();
        });
    });

    describe('a98RgbToXyzD65', function (): void {
        it('converts a98-rgb to xyz', function (): void {
            $xyz = $this->converter->a98RgbChannelsToXyzD65(0.5, 0.5, 0.5);

            expect($xyz->x)->toBeFloat();
        });
    });

    describe('prophotoRgbToXyzD65', function (): void {
        it('converts prophoto-rgb to xyz', function (): void {
            $xyz = $this->converter->prophotoRgbChannelsToXyzD65(0.5, 0.5, 0.5);

            expect($xyz->x)->toBeFloat();
        });
    });

    describe('rec2020RgbToXyzD65', function (): void {
        it('converts rec2020 to xyz', function (): void {
            $xyz = $this->converter->rec2020ChannelsToXyzD65(0.5, 0.5, 0.5);

            expect($xyz->x)->toBeFloat();
        });
    });

    describe('xyzD50ToD65', function (): void {
        it('converts xyz d50 to d65', function (): void {
            [$x, $y, $z] = $this->converter->xyzD50ToD65(0.5, 0.5, 0.5);

            expect($x)->toBeFloat()->and($y)->toBeFloat()->and($z)->toBeFloat();
        });
    });

    describe('rgbToOklch with clampChannels=false', function (): void {
        it('converts without clamping', function (): void {
            $rgb = new RgbColor(1.5, 0.0, 0.0, 1.0);

            $oklch = $this->converter->normalizedSrgbToOklch($rgb, false);

            expect($oklch)->toBeInstanceOf(OklchColor::class);
        });
    });

    describe('hslToRgb with saturation=0', function (): void {
        it('returns achromatic color', function (): void {
            [$r, $g, $b] = $this->converter->hslToRgb(0.0, 0.0, 0.5);

            expect($r)->toBe(0.5)->and($g)->toBe(0.5)->and($b)->toBe(0.5);
        });
    });

    describe('hwbToRgb with whiteness+blackness > 1', function (): void {
        it('handles edge case', function (): void {
            [$r, ,] = $this->converter->hwbToRgb(180.0, 0.8, 0.8);

            expect($r)->toBeFloat();
        });
    });

    describe('hueFromNormalizedRgb when g < b', function (): void {
        it('adds 360 to hue', function (): void {
            // r=1.0 (max), g=0.0 (min), b=0.5, delta=1.0
            // h = 60 * ((0.0 - 0.5) / 1.0) = -30, + 360 = 330
            $channels = new NormalizedRgbChannels(1.0, 0.0, 0.5, 1.0, 1.0, 0.0, 1.0);

            $hue = $this->converter->hueFromNormalizedRgb($channels);

            expect($hue)->toBeCloseTo(330.0, 0.00001);
        });
    });

    describe('xyzD65ToLinearDisplayP3', function (): void {
        it('converts xyz to linear display-p3', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            [$r, $g, $b] = $this->converter->xyzD65ToLinearDisplayP3($xyz);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });
    });

    describe('rgbToDisplayP3', function (): void {
        it('converts rgb to display-p3', function (): void {
            $rgb = new RgbColor(255.0, 0.0, 0.0, 1.0);

            [$r, ,] = $this->converter->rgbToDisplayP3($rgb);

            expect($r)->toBeFloat();
        });
    });

    describe('xyzD65ToDisplayP3', function (): void {
        it('converts xyz to display-p3', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            [$r, ,] = $this->converter->xyzD65ToDisplayP3($xyz);

            expect($r)->toBeFloat();
        });
    });

    describe('xyzD65ToOklch', function (): void {
        it('converts xyz to oklch', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            $oklch = $this->converter->xyzD65ToOklch($xyz);

            expect($oklch)->toBeInstanceOf(OklchColor::class);
        });
    });

    describe('oklabToXyzD65', function (): void {
        it('converts oklab to xyz', function (): void {
            $xyz = $this->converter->oklabChannelsToXyzD65(0.5, 0.1, 0.1);

            expect($xyz)->toBeInstanceOf(XyzColor::class);
        });
    });

    describe('linearToProphotoRgb edge case', function (): void {
        it('handles value <= 1/512 via xyzD50ToProphotoRgb', function (): void {
            $xyz = new XyzColor(0.001, 0.001, 0.001);

            [$r, $g, $b] = $this->converter->xyzD50ToProphotoRgb($xyz);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });
    });

    describe('linearToRec2020 edge case', function (): void {
        it('handles value < 0.0181 via xyzD65ToRec2020', function (): void {
            $xyz = new XyzColor(0.01, 0.01, 0.01);

            [$r, $g, $b] = $this->converter->xyzD65ToRec2020($xyz);

            expect($r)->toBeFloat()->and($g)->toBeFloat()->and($b)->toBeFloat();
        });
    });

    describe('prophotoToLinear edge case', function (): void {
        it('handles value <= 16/512 via prophotoRgbToXyzD65', function (): void {
            $xyz = $this->converter->prophotoRgbChannelsToXyzD65(0.02, 0.02, 0.02);

            expect($xyz->x)->toBeFloat();
        });
    });

    describe('rec2020ToLinear edge case', function (): void {
        it('handles value < 0.08145 via rec2020RgbToXyzD65', function (): void {
            $xyz = $this->converter->rec2020ChannelsToXyzD65(0.05, 0.05, 0.05);

            expect($xyz->x)->toBeFloat();
        });
    });

    describe('rgbToA98Rgb', function (): void {
        it('converts rgb to a98-rgb', function (): void {
            $rgb = new RgbColor(255.0, 0.0, 0.0, 1.0);

            [$r, ,] = $this->converter->rgbToA98Rgb($rgb);

            expect($r)->toBeFloat();
        });
    });

    describe('xyzD65ToA98Rgb', function (): void {
        it('converts xyz to a98-rgb', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            [$r, ,] = $this->converter->xyzD65ToA98Rgb($xyz);

            expect($r)->toBeFloat();
        });
    });

    describe('rgbToProphotoRgb', function (): void {
        it('converts rgb to prophoto-rgb', function (): void {
            $rgb = new RgbColor(255.0, 0.0, 0.0, 1.0);

            [$r, ,] = $this->converter->rgbToProphotoRgb($rgb);

            expect($r)->toBeFloat();
        });
    });

    describe('rgbToRec2020', function (): void {
        it('converts rgb to rec2020', function (): void {
            $rgb = new RgbColor(255.0, 0.0, 0.0, 1.0);

            [$r, ,] = $this->converter->rgbToRec2020($rgb);

            expect($r)->toBeFloat();
        });
    });

    describe('xyzD65ToRec2020', function (): void {
        it('converts xyz to rec2020', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            [$r, ,] = $this->converter->xyzD65ToRec2020($xyz);

            expect($r)->toBeFloat();
        });
    });

    describe('oklchToLch', function (): void {
        it('converts oklch to lch', function (): void {
            $oklch = new OklchColor(50.0, 0.2, 180.0, 1.0);

            $lch = $this->converter->oklchToLch($oklch);

            expect($lch)->toBeInstanceOf(LchColor::class);
        });
    });

    describe('rgbToLch', function (): void {
        it('converts rgb to lch', function (): void {
            $rgb = new RgbColor(255.0, 0.0, 0.0, 1.0);

            $lch = $this->converter->rgbToLch($rgb);

            expect($lch)->toBeInstanceOf(LchColor::class);
        });

        it('returns achromatic lch for gray', function (): void {
            $rgb = new RgbColor(128.0, 128.0, 128.0, 1.0);

            $lch = $this->converter->rgbToLch($rgb);

            expect($lch->c)->toBe(0.0);
        });
    });

    describe('xyzD50ToLch', function (): void {
        it('converts xyz d50 to lch', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            $lch = $this->converter->xyzD50ToLch($xyz);

            expect($lch)->toBeInstanceOf(LchColor::class);
        });
    });

    describe('rgbToOklch with clampChannels=false - additional', function (): void {
        it('covers oklabComponentsToOklch with negative hue', function (): void {
            $rgb = new RgbColor(0.0, 0.0, 255.0, 1.0);

            $oklch = $this->converter->normalizedSrgbToOklch($rgb, false);

            expect($oklch)->toBeInstanceOf(OklchColor::class);
        });
    });

    describe('labToXyzD50', function (): void {
        it('converts lab to xyz d50', function (): void {
            $xyz = $this->converter->labToXyzD50(50.0, 0.0, 0.0);

            expect($xyz)->toBeInstanceOf(XyzColor::class);
        });

        it('converts lab with high a and b values', function (): void {
            $xyz = $this->converter->labToXyzD50(50.0, 50.0, 50.0);

            expect($xyz->x)->toBeFloat();
        });
    });

    describe('labChannelsToXyzD65', function (): void {
        it('converts lab to xyz d65', function (): void {
            $xyz = $this->converter->labChannelsToXyzD65(50.0, 20.0, -10.0);

            expect($xyz)->toBeInstanceOf(XyzColor::class)
                ->and($xyz->x)->toBeFloat()
                ->and($xyz->y)->toBeFloat()
                ->and($xyz->z)->toBeFloat();
        });
    });

    describe('lchChannelsToXyzD65', function (): void {
        it('converts lch to xyz d65', function (): void {
            $xyz = $this->converter->lchChannelsToXyzD65(50.0, 30.0, 180.0);

            expect($xyz)->toBeInstanceOf(XyzColor::class)
                ->and($xyz->x)->toBeFloat()
                ->and($xyz->y)->toBeFloat()
                ->and($xyz->z)->toBeFloat();
        });
    });

    describe('oklchChannelsToXyzD65', function (): void {
        it('converts oklch to xyz d65', function (): void {
            $xyz = $this->converter->oklchChannelsToXyzD65(0.6, 0.15, 30.0);

            expect($xyz)->toBeInstanceOf(XyzColor::class)
                ->and($xyz->x)->toBeFloat()
                ->and($xyz->y)->toBeFloat()
                ->and($xyz->z)->toBeFloat();
        });
    });

    describe('calculateDeltaE', function (): void {
        it('calculates delta e between two colors', function (): void {
            $rgb1 = new RgbColor(255.0, 0.0, 0.0, 1.0);
            $rgb2 = new RgbColor(0.0, 255.0, 0.0, 1.0);

            $deltaE = $this->converter->calculateDeltaE($rgb1, $rgb2);

            expect($deltaE)->toBeFloat()->and($deltaE)->toBeGreaterThan(0.0);
        });

        it('returns 0 for same colors', function (): void {
            $rgb = new RgbColor(128.0, 128.0, 128.0, 1.0);

            $deltaE = $this->converter->calculateDeltaE($rgb, $rgb);

            expect($deltaE)->toBe(0.0);
        });
    });

    describe('normalizedSrgbToOklch', function (): void {
        it('converts normalized srgb to oklch', function (): void {
            $rgb = new RgbColor(1.0, 0.0, 0.0, 1.0);

            $oklch = $this->converter->normalizedSrgbToOklch($rgb);

            expect($oklch)->toBeInstanceOf(OklchColor::class);
        });

        it('converts with clampChannels=true', function (): void {
            $rgb = new RgbColor(1.5, -0.5, 0.5, 1.0);

            $oklch = $this->converter->normalizedSrgbToOklch($rgb, true);

            expect($oklch)->toBeInstanceOf(OklchColor::class);
        });
    });

    describe('xyzToLabD50', function (): void {
        it('converts xyz d50 white to lab white', function (): void {
            $xyz = new XyzColor(0.9642956764295677, 1.0, 0.8251046025104602);

            [$l, $a, $b] = $this->converter->xyzToLabD50($xyz);

            expect($l)->toBeCloseTo(100.0, 1.0)
                ->and($a)->toBeCloseTo(0.0, 1.0)
                ->and($b)->toBeCloseTo(0.0, 1.0);
        });

        it('converts xyz d50 black to lab black', function (): void {
            $xyz = new XyzColor(0.0, 0.0, 0.0);

            [$l, $a, $b] = $this->converter->xyzToLabD50($xyz);

            expect($l)->toBe(0.0)
                ->and($a)->toBe(0.0)
                ->and($b)->toBe(0.0);
        });

        it('converts xyz with values below epsilon', function (): void {
            $xyz = new XyzColor(0.0001, 0.0001, 0.0001);

            [$l, $a, $b] = $this->converter->xyzToLabD50($xyz);

            expect($l)->toBeFloat()
                ->and($a)->toBeFloat()
                ->and($b)->toBeFloat();
        });
    });

    describe('xyzToLchD50', function (): void {
        it('converts xyz d50 to lch', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            [$l, $c, $h] = $this->converter->xyzToLchD50($xyz);

            expect($l)->toBeFloat()
                ->and($c)->toBeFloat()
                ->and($h)->toBeFloat();
        });

        it('converts xyz with negative hue and normalizes it', function (): void {
            $xyz = new XyzColor(0.1, 0.5, 0.8);

            [, , $h] = $this->converter->xyzToLchD50($xyz);

            expect($h)->toBeGreaterThanOrEqual(0.0)
                ->and($h)->toBeLessThan(360.0);
        });
    });

    describe('xyzToOklabD65', function (): void {
        it('converts xyz d65 white to oklab', function (): void {
            $xyz = new XyzColor(0.9505, 1.0, 1.0890);

            [$l, $a, $b] = $this->converter->xyzToOklabD65($xyz);

            expect($l)->toBeCloseTo(1.0, 0.1)
                ->and($a)->toBeCloseTo(0.0, 0.1)
                ->and($b)->toBeCloseTo(0.0, 0.1);
        });

        it('converts xyz d65 black to oklab', function (): void {
            $xyz = new XyzColor(0.0, 0.0, 0.0);

            [$l, $a, $b] = $this->converter->xyzToOklabD65($xyz);

            expect($l)->toBe(0.0)
                ->and($a)->toBe(0.0)
                ->and($b)->toBe(0.0);
        });

        it('converts xyz with cube root calculations', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            [$l, $a, $b] = $this->converter->xyzToOklabD65($xyz);

            expect($l)->toBeFloat()
                ->and($a)->toBeFloat()
                ->and($b)->toBeFloat();
        });
    });

    describe('xyzToOklchD65', function (): void {
        it('converts xyz d65 to oklch', function (): void {
            $xyz = new XyzColor(0.5, 0.5, 0.5);

            [$l, $c, $h] = $this->converter->xyzToOklchD65($xyz);

            expect($l)->toBeFloat()
                ->and($c)->toBeFloat()
                ->and($h)->toBeFloat();
        });

        it('converts xyz with negative hue and normalizes it', function (): void {
            $xyz = new XyzColor(0.1, 0.5, 0.8);

            [, , $h] = $this->converter->xyzToOklchD65($xyz);

            expect($h)->toBeGreaterThanOrEqual(0.0)
                ->and($h)->toBeLessThan(360.0);
        });
    });

    describe('oklabToSrgb', function (): void {
        it('converts oklab white to srgb white', function (): void {
            [$r, $g, $b] = $this->converter->oklabToSrgb(1.0, 0.0, 0.0);

            expect($r)->toBeCloseTo(1.0, 0.1)
                ->and($g)->toBeCloseTo(1.0, 0.1)
                ->and($b)->toBeCloseTo(1.0, 0.1);
        });

        it('converts oklab black to srgb black', function (): void {
            [$r, $g, $b] = $this->converter->oklabToSrgb(0.0, 0.0, 0.0);

            expect($r)->toBeCloseTo(0.0, 0.1)
                ->and($g)->toBeCloseTo(0.0, 0.1)
                ->and($b)->toBeCloseTo(0.0, 0.1);
        });
    });

    describe('oklabToSrgbUnclamped', function (): void {
        it('converts oklab to srgb without clamping', function (): void {
            [$r, $g, $b] = $this->converter->oklabToSrgbUnclamped(0.5, 0.1, -0.1);

            expect($r)->toBeFloat()
                ->and($g)->toBeFloat()
                ->and($b)->toBeFloat();
        });
    });

    describe('typed color helpers', function (): void {
        it('converts LabColor to srgb RgbColor while preserving alpha', function (): void {
            $rgb = $this->converter->labToRgbColor(new LabColor(100.0, 0.0, 0.0, 0.25));

            expect($rgb->r)->toBeCloseTo(1.0, 0.01)
                ->and($rgb->g)->toBeCloseTo(1.0, 0.01)
                ->and($rgb->b)->toBeCloseTo(1.0, 0.01)
                ->and($rgb->a)->toBe(0.25);
        });

        it('converts xyz d50 to LabColor while preserving alpha', function (): void {
            $lab = $this->converter->xyzD50ToLabColor(new XyzColor(0.9642956764295677, 1.0, 0.8251046025104602), 0.4);

            expect($lab)->toBeInstanceOf(LabColor::class)
                ->and($lab->l)->toBeCloseTo(100.0, 0.000001)
                ->and($lab->a)->toBeCloseTo(0.0, 0.000001)
                ->and($lab->b)->toBeCloseTo(0.0, 0.000001)
                ->and($lab->alpha)->toBe(0.4);
        });

        it('converts xyz d65 to OklabColor while preserving alpha', function (): void {
            $oklab = $this->converter->xyzD65ToOklabColor(new XyzColor(0.9504559270516716, 1.0, 1.0890577507598784), 0.6);

            expect($oklab)->toBeInstanceOf(OklabColor::class)
                ->and($oklab->l)->toBeCloseTo(100.0, 0.000001)
                ->and($oklab->a)->toBeCloseTo(0.0, 0.000001)
                ->and($oklab->b)->toBeCloseTo(0.0, 0.000001)
                ->and($oklab->alpha)->toBe(0.6);
        });
    });

    describe('hueFromNormalizedRgb when green is max', function (): void {
        it('calculates hue when green is max', function (): void {
            $channels = new NormalizedRgbChannels(
                r: 0.2,
                g: 1.0,
                b: 0.3,
                a: 1.0,
                max: 1.0,
                min: 0.2,
                delta: 0.8
            );

            $hue = $this->converter->hueFromNormalizedRgb($channels);

            expect($hue)->toBeFloat()
                ->and($hue)->toBeGreaterThan(100.0)
                ->and($hue)->toBeLessThan(180.0);
        });
    });

    describe('hueFromNormalizedRgb when blue is max', function (): void {
        it('calculates hue when blue is max', function (): void {
            $channels = new NormalizedRgbChannels(
                r: 0.2,
                g: 0.3,
                b: 1.0,
                a: 1.0,
                max: 1.0,
                min: 0.2,
                delta: 0.8
            );

            $hue = $this->converter->hueFromNormalizedRgb($channels);

            expect($hue)->toBeFloat()
                ->and($hue)->toBeGreaterThan(200.0)
                ->and($hue)->toBeLessThan(260.0);
        });
    });

    describe('rgbToXyzD65', function (): void {
        it('converts rgb white to xyz d65 white', function (): void {
            $rgb = new RgbColor(255.0, 255.0, 255.0, 1.0);
            $xyz = $this->converter->rgbToXyzD65($rgb);

            expect($xyz->x)->toBeCloseTo(0.9505, 0.1)
                ->and($xyz->y)->toBeCloseTo(1.0, 0.1)
                ->and($xyz->z)->toBeCloseTo(1.089, 0.1);
        });

        it('converts rgb black to xyz d65 black', function (): void {
            $rgb = new RgbColor(0.0, 0.0, 0.0, 1.0);
            $xyz = $this->converter->rgbToXyzD65($rgb);

            expect($xyz->x)->toBe(0.0)
                ->and($xyz->y)->toBe(0.0)
                ->and($xyz->z)->toBe(0.0);
        });
    });

    describe('xyzD65ToSrgba', function (): void {
        it('converts xyz d65 white to srgb white', function (): void {
            $xyz = new XyzColor(0.9505, 1.0, 1.089);
            $rgb = $this->converter->xyzD65ToSrgba($xyz, 1.0);

            expect($rgb->r)->toBeCloseTo(1.0, 0.1)
                ->and($rgb->g)->toBeCloseTo(1.0, 0.1)
                ->and($rgb->b)->toBeCloseTo(1.0, 0.1)
                ->and($rgb->a)->toBe(1.0);
        });

        it('converts xyz d65 with custom opacity', function (): void {
            $xyz = new XyzColor(0.9505, 1.0, 1.089);
            $rgb = $this->converter->xyzD65ToSrgba($xyz, 0.5);

            expect($rgb->a)->toBe(0.5);
        });
    });
});
