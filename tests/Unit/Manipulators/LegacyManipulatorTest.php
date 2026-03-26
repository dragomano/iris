<?php

declare(strict_types=1);

use Bugo\Iris\Converters\ModelConverter;
use Bugo\Iris\Manipulators\LegacyManipulator;
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\RgbColor;

describe('LegacyManipulator', function (): void {
    beforeEach(function (): void {
        $this->manipulator = new LegacyManipulator();
        $this->converter   = new ModelConverter();

        // Red color: rgb(255,0,0) corresponds to hsl(0, 100%, 50%)
        $this->red    = new RgbColor(255.0, 0.0, 0.0, 1.0);
        $this->redHsl = new HslColor(0.0, 100.0, 50.0, 1.0);
    });

    describe('scale', function (): void {
        it('scales direct rgb channel upward', function (): void {
            $result = $this->manipulator->scale(
                new RgbColor(100.0, 50.0, 25.0, 1.0),
                new HslColor(0.0, 50.0, 50.0, 1.0),
                ['red' => 50.0],
            );

            expect($result->r)->toBe(177.5)
                ->and($result->g)->toBe(50.0)
                ->and($result->b)->toBe(25.0)
                ->and($result->a)->toBe(1.0);
        });

        it('scales saturation and lightness through hsl conversion', function (): void {
            $result = $this->manipulator->scale(
                new RgbColor(255.0, 0.0, 0.0, 1.0),
                new HslColor(0.0, 100.0, 50.0, 1.0),
                ['saturation' => -100.0],
            );

            expect($result->r)->toBe(127.5)
                ->and($result->g)->toBe(127.5)
                ->and($result->b)->toBe(127.5);
        });
    });

    describe('grayscale', function (): void {
        it('sets saturation to zero while keeping lightness', function (): void {
            $color  = new HslColor(h: 120.0, s: 100.0, l: 50.0, a: 1.0);
            $result = $this->manipulator->grayscale($color);

            expect($result->s)->toBe(0.0)
                ->and($result->l)->toBe(50.0);
        });

        it('preserves hue value', function (): void {
            $color  = new HslColor(h: 240.0, s: 80.0, l: 60.0, a: 1.0);
            $result = $this->manipulator->grayscale($color);

            expect($result->h)->toBe(240.0)
                ->and($result->s)->toBe(0.0);
        });

        it('preserves alpha', function (): void {
            $color  = new HslColor(h: 0.0, s: 50.0, l: 50.0, a: 0.5);
            $result = $this->manipulator->grayscale($color);

            expect($result->a)->toBe(0.5);
        });

        it('on already gray color returns same saturation', function (): void {
            $color  = new HslColor(h: 0.0, s: 0.0, l: 75.0, a: 1.0);
            $result = $this->manipulator->grayscale($color);

            expect($result->s)->toBe(0.0)
                ->and($result->l)->toBe(75.0);
        });
    });

    describe('mix', function (): void {
        it('mix(red, blue, 0.5) produces equal red and blue channels', function (): void {
            $red    = new RgbColor(255.0, 0.0, 0.0, 1.0);
            $blue   = new RgbColor(0.0, 0.0, 255.0, 1.0);
            $result = $this->manipulator->mix($red, $blue, 0.5);

            expect(round($result->g, 4))->toBe(0.0)
                ->and($result->r)->toBe($result->b);
        });

        it('mix(red, blue, 1.0) returns left color', function (): void {
            $red    = new RgbColor(255.0, 0.0, 0.0, 1.0);
            $blue   = new RgbColor(0.0, 0.0, 255.0, 1.0);
            $result = $this->manipulator->mix($red, $blue, 1.0);

            expect($result->r)->toBe(255.0)
                ->and($result->g)->toBe(0.0)
                ->and($result->b)->toBe(0.0);
        });

        it('mix(red, blue, 0.0) returns right color', function (): void {
            $red    = new RgbColor(255.0, 0.0, 0.0, 1.0);
            $blue   = new RgbColor(0.0, 0.0, 255.0, 1.0);
            $result = $this->manipulator->mix($red, $blue, 0.0);

            expect($result->r)->toBe(0.0)
                ->and($result->g)->toBe(0.0)
                ->and($result->b)->toBe(255.0);
        });

        it('mix preserves alpha proportionally', function (): void {
            $a      = new RgbColor(255.0, 0.0, 0.0, 1.0);
            $b      = new RgbColor(0.0, 0.0, 255.0, 0.0);
            $result = $this->manipulator->mix($a, $b, 0.5);

            expect($result->a)->toBe(0.5);
        });
    });

    describe('invert', function (): void {
        it('invert(rgb(255,0,0), 1.0) produces rgb(0,255,255)', function (): void {
            $color  = new RgbColor(255.0, 0.0, 0.0, 1.0);
            $result = $this->manipulator->invert($color, 1.0);

            expect($result->r)->toBe(0.0)
                ->and($result->g)->toBe(255.0)
                ->and($result->b)->toBe(255.0);
        });

        it('invert(rgb(255,0,0), 0.0) returns original unchanged', function (): void {
            $color  = new RgbColor(255.0, 0.0, 0.0, 1.0);
            $result = $this->manipulator->invert($color, 0.0);

            expect($result->r)->toBe(255.0)
                ->and($result->g)->toBe(0.0)
                ->and($result->b)->toBe(0.0);
        });

        it('invert(rgb(128,128,128), 1.0) produces near rgb(127,127,127)', function (): void {
            $color  = new RgbColor(128.0, 128.0, 128.0, 1.0);
            $result = $this->manipulator->invert($color, 1.0);

            expect(round($result->r))->toBe(127.0)
                ->and(round($result->g))->toBe(127.0)
                ->and(round($result->b))->toBe(127.0);
        });

        it('invert preserves alpha', function (): void {
            $color  = new RgbColor(100.0, 100.0, 100.0, 0.6);
            $result = $this->manipulator->invert($color, 1.0);

            expect($result->a)->toBe(0.6);
        });
    });

    describe('adjustHue', function (): void {
        it('adjustHue increases hue by 30 degrees', function (): void {
            $result   = $this->manipulator->adjustHue($this->red, $this->redHsl, 30.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(30.0, 100.0, 50.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r))
                ->and(round($result->g))->toBe(round($expected->g))
                ->and(round($result->b))->toBe(round($expected->b));
        });

        it('adjustHue wraps around 360 degrees', function (): void {
            $result   = $this->manipulator->adjustHue($this->red, $this->redHsl, 360.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(0.0, 100.0, 50.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r));
        });
    });

    describe('adjustAlpha', function (): void {
        it('adjustAlpha decreases alpha by 0.3', function (): void {
            $result = $this->manipulator->adjustAlpha($this->red, $this->redHsl, -0.3);

            expect(round($result->a, 4))->toBe(0.7);
        });

        it('adjustAlpha increases alpha', function (): void {
            $dimRed    = new RgbColor(255.0, 0.0, 0.0, 0.5);
            $dimRedHsl = new HslColor(0.0, 100.0, 50.0, 0.5);
            $result    = $this->manipulator->adjustAlpha($dimRed, $dimRedHsl, 0.3);

            expect(round($result->a, 4))->toBe(0.8);
        });

        it('adjustAlpha clamps to maximum 1.0', function (): void {
            $result = $this->manipulator->adjustAlpha($this->red, $this->redHsl, 0.5);

            expect($result->a)->toBe(1.0);
        });
    });

    describe('adjustLightness', function (): void {
        it('adjustLightness increases lightness', function (): void {
            $result   = $this->manipulator->adjustLightness($this->red, $this->redHsl, 10.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(0.0, 100.0, 60.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r))
                ->and(round($result->g))->toBe(round($expected->g))
                ->and(round($result->b))->toBe(round($expected->b));
        });

        it('adjustLightness decreases lightness', function (): void {
            $result   = $this->manipulator->adjustLightness($this->red, $this->redHsl, -10.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(0.0, 100.0, 40.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r));
        });

        it('adjustLightness clamps to 100', function (): void {
            $result   = $this->manipulator->adjustLightness($this->red, $this->redHsl, 200.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(0.0, 100.0, 100.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r));
        });
    });

    describe('adjust', function (): void {
        it('adjust with red channel increases red value', function (): void {
            $dark   = new RgbColor(100.0, 50.0, 50.0, 1.0);
            $result = $this->manipulator->adjust($dark, $this->redHsl, ['red' => 50.0]);

            expect($result->r)->toBe(150.0)
                ->and($result->g)->toBe(50.0)
                ->and($result->b)->toBe(50.0);
        });
    });

    describe('change', function (): void {
        it('change with alpha sets alpha to new value', function (): void {
            $result = $this->manipulator->change($this->red, $this->redHsl, ['alpha' => 0.3]);

            expect(round($result->a, 4))->toBe(0.3);
        });
    });

    describe('darken', function (): void {
        it('darken decreases lightness', function (): void {
            // red = hsl(0, 100%, 50%); darken 10% => hsl(0, 100%, 40%)
            $result   = $this->manipulator->darken($this->red, 10.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(0.0, 100.0, 40.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r))
                ->and(round($result->g))->toBe(round($expected->g))
                ->and(round($result->b))->toBe(round($expected->b));
        });

        it('darken clamps lightness to 0', function (): void {
            $result = $this->manipulator->darken($this->red, 200.0);

            expect($result->r)->toBe(0.0)
                ->and($result->g)->toBe(0.0)
                ->and($result->b)->toBe(0.0);
        });
    });

    describe('lighten', function (): void {
        it('lighten increases lightness', function (): void {
            // red = hsl(0, 100%, 50%); lighten 10% => hsl(0, 100%, 60%)
            $result   = $this->manipulator->lighten($this->red, 10.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(0.0, 100.0, 60.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r))
                ->and(round($result->g))->toBe(round($expected->g))
                ->and(round($result->b))->toBe(round($expected->b));
        });

        it('lighten clamps lightness to 100', function (): void {
            $result = $this->manipulator->lighten($this->red, 200.0);

            // hsl(*, *, 100%) = white
            expect($result->r)->toBe(255.0)
                ->and($result->g)->toBe(255.0)
                ->and($result->b)->toBe(255.0);
        });
    });

    describe('saturate', function (): void {
        it('saturate increases saturation', function (): void {
            // hsl(200, 50%, 50%); saturate 20% => hsl(200, 70%, 50%)
            $color    = $this->converter->hslToRgbColor(new HslColor(200.0, 50.0, 50.0, 1.0));
            $result   = $this->manipulator->saturate($color, 20.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(200.0, 70.0, 50.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r))
                ->and(round($result->g))->toBe(round($expected->g))
                ->and(round($result->b))->toBe(round($expected->b));
        });

        it('saturate clamps saturation to 100', function (): void {
            $result = $this->manipulator->saturate($this->red, 200.0);

            // already 100% saturated, stays red
            expect(round($result->r))->toBe(255.0);
        });
    });

    describe('desaturate', function (): void {
        it('desaturate decreases saturation', function (): void {
            // hsl(0, 100%, 50%); desaturate 40% => hsl(0, 60%, 50%)
            $result   = $this->manipulator->desaturate($this->red, 40.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(0.0, 60.0, 50.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r))
                ->and(round($result->g))->toBe(round($expected->g))
                ->and(round($result->b))->toBe(round($expected->b));
        });

        it('desaturate clamps saturation to 0', function (): void {
            $result = $this->manipulator->desaturate($this->red, 200.0);
            // fully desaturated red = gray with same lightness hsl(0, 0%, 50%)
            $expected = $this->converter->hslToRgbColor(new HslColor(0.0, 0.0, 50.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r))
                ->and(round($result->g))->toBe(round($expected->g))
                ->and(round($result->b))->toBe(round($expected->b));
        });
    });

    describe('fadeIn', function (): void {
        it('fadeIn increases alpha', function (): void {
            $dimRed = new RgbColor(255.0, 0.0, 0.0, 0.4);
            $result = $this->manipulator->fadeIn($dimRed, 0.3);

            expect(round($result->a, 4))->toBe(0.7);
        });

        it('fadeIn clamps alpha to 1.0', function (): void {
            $result = $this->manipulator->fadeIn($this->red, 0.5);

            expect($result->a)->toBe(1.0);
        });
    });

    describe('fadeOut', function (): void {
        it('fadeOut decreases alpha', function (): void {
            $result = $this->manipulator->fadeOut($this->red, 0.3);

            expect(round($result->a, 4))->toBe(0.7);
        });

        it('fadeOut clamps alpha to 0.0', function (): void {
            $result = $this->manipulator->fadeOut($this->red, 2.0);

            expect($result->a)->toBe(0.0);
        });
    });

    describe('spin', function (): void {
        it('spin rotates hue by 30 degrees', function (): void {
            // red = hsl(0, 100%, 50%); spin 30 => hsl(30, 100%, 50%) = orange
            $result   = $this->manipulator->spin($this->red, 30.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(30.0, 100.0, 50.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r))
                ->and(round($result->g))->toBe(round($expected->g))
                ->and(round($result->b))->toBe(round($expected->b));
        });

        it('spin wraps around 360 degrees', function (): void {
            $result   = $this->manipulator->spin($this->red, 360.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(0.0, 100.0, 50.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r));
        });

        it('spin handles negative degrees', function (): void {
            // spin(-30) = hsl(330, 100%, 50%) = hot pink
            $result   = $this->manipulator->spin($this->red, -30.0);
            $expected = $this->converter->hslToRgbColor(new HslColor(330.0, 100.0, 50.0, 1.0));

            expect(round($result->r))->toBe(round($expected->r))
                ->and(round($result->g))->toBe(round($expected->g))
                ->and(round($result->b))->toBe(round($expected->b));
        });
    });
});
