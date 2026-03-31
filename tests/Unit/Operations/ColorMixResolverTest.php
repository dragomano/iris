<?php

declare(strict_types=1);

use Bugo\Iris\Operations\ColorMixResolver;
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\LchColor;
use Bugo\Iris\Spaces\OklabColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;

describe('ColorMixResolver', function (): void {
    beforeEach(function (): void {
        $this->resolver = new ColorMixResolver();
    });

    describe('mixSrgb', function (): void {
        it('weight=1.0 returns first color', function (): void {
            $a = new RgbColor(r: 255.0, g: 0.0, b: 0.0, a: 1.0);
            $b = new RgbColor(r: 0.0, g: 0.0, b: 255.0, a: 1.0);

            $result = $this->resolver->mixSrgb($a, $b, 1.0);

            expect($result->r)->toBeCloseTo(255.0)
                ->and($result->b)->toBeCloseTo(0.0);
        });

        it('weight=0.0 returns second color', function (): void {
            $a = new RgbColor(r: 255.0, g: 0.0, b: 0.0, a: 1.0);
            $b = new RgbColor(r: 0.0, g: 0.0, b: 255.0, a: 1.0);

            $result = $this->resolver->mixSrgb($a, $b, 0.0);

            expect($result->r)->toBeCloseTo(0.0)
                ->and($result->b)->toBeCloseTo(255.0);
        });

        it('weight=0.5 produces midpoint', function (): void {
            $a = new RgbColor(r: 255.0, g: 0.0, b: 0.0, a: 1.0);
            $b = new RgbColor(r: 0.0, g: 0.0, b: 255.0, a: 1.0);

            $result = $this->resolver->mixSrgb($a, $b, 0.5);

            expect($result->r)->toBeCloseTo(127.5)
                ->and($result->b)->toBeCloseTo(127.5);
        });

        it('alpha is interpolated', function (): void {
            $a = new RgbColor(r: 255.0, g: 0.0, b: 0.0, a: 1.0);
            $b = new RgbColor(r: 0.0, g: 0.0, b: 255.0, a: 0.0);

            $result = $this->resolver->mixSrgb($a, $b, 0.5);

            expect($result->a)->toBeCloseTo(0.5);
        });
    });

    describe('mixHsl', function (): void {
        it('weight=0.5 midpoint', function (): void {
            $a = new HslColor(h: 0.0, s: 100.0, l: 50.0, a: 1.0);
            $b = new HslColor(h: 120.0, s: 100.0, l: 50.0, a: 1.0);

            $result = $this->resolver->mixHsl($a, $b, 0.5);

            expect($result->h)->toBeCloseTo(60.0);
        });

        it('hue method longer adjusts direction', function (): void {
            $a = new HslColor(h: 10.0, s: 100.0, l: 50.0, a: 1.0);
            $b = new HslColor(h: 50.0, s: 100.0, l: 50.0, a: 1.0);

            $shorter = $this->resolver->mixHsl($a, $b, 0.5, 'shorter');
            $longer  = $this->resolver->mixHsl($a, $b, 0.5, 'longer');

            // shorter = 30, longer goes the other way around: (10+50+360)/2 mod 360 = 210
            expect($shorter->h)->toBeCloseTo(30.0)
                ->and($longer->h)->toBeCloseTo(210.0);
        });
    });

    describe('mixOklab', function (): void {
        it('weight=0.5 produces midpoint channels', function (): void {
            $a = new OklabColor(l: 0.0, a: 0.0, b: 0.0, alpha: 1.0);
            $b = new OklabColor(l: 1.0, a: 0.2, b: -0.1, alpha: 1.0);

            $result = $this->resolver->mixOklab($a, $b, 0.5);

            expect($result->l)->toBeCloseTo(0.5)
                ->and($result->a)->toBeCloseTo(0.1)
                ->and($result->b)->toBeCloseTo(-0.05);
        });
    });

    describe('mixOklch', function (): void {
        it('weight=1.0 returns first color', function (): void {
            $a = new OklchColor(l: 70.0, c: 20.0, h: 30.0, a: 1.0);
            $b = new OklchColor(l: 50.0, c: 10.0, h: 200.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 1.0);

            expect($result->l)->toBeCloseTo(70.0)
                ->and($result->c)->toBeCloseTo(20.0);
        });

        it('hue method increasing adjusts direction', function (): void {
            $a = new OklchColor(l: 50.0, c: 10.0, h: 300.0, a: 1.0);
            $b = new OklchColor(l: 50.0, c: 10.0, h: 60.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5, 'increasing');

            // increasing: h2 < h1 → h2 += 360, so h2=420; mid = (300+420)/2 = 360 → 0
            expect($result->h ?? 0.0)->toBeCloseTo(0.0, 2.0);
        });

        it('hue method decreasing adjusts direction', function (): void {
            $a = new OklchColor(l: 50.0, c: 10.0, h: 60.0, a: 1.0);
            $b = new OklchColor(l: 50.0, c: 10.0, h: 300.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5, 'decreasing');

            // decreasing: h1 < h2 → h1 += 360, so h1=420; mid = (420+300)/2 = 360 → 0
            expect($result->h ?? 0.0)->toBeCloseTo(0.0, 2.0);
        });
    });

    describe('mixLab', function (): void {
        it('weight=0.5 produces midpoint', function (): void {
            $a = new LabColor(l: 0.0, a: 0.0, b: 0.0, alpha: 1.0);
            $b = new LabColor(l: 100.0, a: 50.0, b: -50.0, alpha: 1.0);

            $result = $this->resolver->mixLab($a, $b, 0.5);

            expect($result->l)->toBeCloseTo(50.0)
                ->and($result->a)->toBeCloseTo(25.0)
                ->and($result->b)->toBeCloseTo(-25.0);
        });
    });

    describe('mixLch', function (): void {
        it('weight=0.5 interpolates lightness and chroma', function (): void {
            $a = new LchColor(l: 0.0, c: 0.0, h: 0.0);
            $b = new LchColor(l: 100.0, c: 60.0, h: 180.0);

            $result = $this->resolver->mixLch($a, $b, 0.5);

            expect($result->l)->toBeCloseTo(50.0)
                ->and($result->c)->toBeCloseTo(30.0);
        });

        it('interpolates alpha channel', function (): void {
            $a = new LchColor(l: 50.0, c: 30.0, h: 90.0, alpha: 0.0);
            $b = new LchColor(l: 50.0, c: 30.0, h: 90.0, alpha: 1.0);

            $result = $this->resolver->mixLch($a, $b, 0.5);

            expect($result->alpha)->toBeCloseTo(0.5);
        });
    });

    describe('none channel handling (OklchColor)', function (): void {
        it('both channels none returns none', function (): void {
            $a = new OklchColor(l: null, c: 10.0, h: 30.0, a: 1.0);
            $b = new OklchColor(l: null, c: 10.0, h: 30.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5);

            expect($result->l)->toBeNull();
        });

        it('none on first side inherits second', function (): void {
            $a = new OklchColor(l: null, c: 10.0, h: 30.0, a: 1.0);
            $b = new OklchColor(l: 70.0, c: 10.0, h: 30.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5);

            expect($result->l)->toBeCloseTo(70.0);
        });

        it('none on second side inherits first', function (): void {
            $a = new OklchColor(l: 50.0, c: 10.0, h: 30.0, a: 1.0);
            $b = new OklchColor(l: null, c: 10.0, h: 30.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5);

            expect($result->l)->toBeCloseTo(50.0);
        });

        it('none hue on both sides returns none hue', function (): void {
            $a = new OklchColor(l: 50.0, c: 10.0, h: null, a: 1.0);
            $b = new OklchColor(l: 70.0, c: 10.0, h: null, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5);

            expect($result->h)->toBeNull();
        });

        it('none hue on second side inherits first hue', function (): void {
            $a = new OklchColor(l: 50.0, c: 10.0, h: 120.0, a: 1.0);
            $b = new OklchColor(l: 70.0, c: 10.0, h: null, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5);

            expect($result->h)->toBeCloseTo(120.0);
        });

        it('none hue on first side inherits second hue', function (): void {
            $a = new OklchColor(l: 50.0, c: 10.0, h: null, a: 1.0);
            $b = new OklchColor(l: 70.0, c: 10.0, h: 240.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5);

            expect($result->h)->toBeCloseTo(240.0);
        });
    });

    describe('mixSrgb premultiplied', function (): void {
        it('uses premultiplied alpha when flag is true', function (): void {
            $a = new RgbColor(r: 1.0, g: 0.0, b: 0.0, a: 0.5);
            $b = new RgbColor(r: 0.0, g: 0.0, b: 1.0, a: 1.0);

            $result = $this->resolver->mixSrgb($a, $b, 0.5, premultiplied: true);

            // resultAlpha = 0.5*0.5 + 1.0*0.5 = 0.75
            // rPremult = 1.0*0.5 = 0.5, rPremult2 = 0.0*1.0 = 0.0
            // rMixed = 0.5*0.5 + 0.0*0.5 = 0.25; r = 0.25/0.75 ≈ 0.333
            expect($result->r)->toBeCloseTo(0.333, 2)
                ->and($result->a)->toBeCloseTo(0.75);
        });

        it('returns black when result alpha is zero', function (): void {
            $a = new RgbColor(r: 1.0, g: 0.5, b: 0.25, a: 0.0);
            $b = new RgbColor(r: 0.5, g: 0.25, b: 1.0, a: 0.0);

            $result = $this->resolver->mixSrgb($a, $b, 0.5, premultiplied: true);

            expect($result->r)->toBe(0.0)
                ->and($result->g)->toBe(0.0)
                ->and($result->b)->toBe(0.0)
                ->and($result->a)->toBe(0.0);
        });

        it('handles null channels in premultiplied mode', function (): void {
            $a = new RgbColor(r: null, g: 0.0, b: 0.0, a: 0.5);
            $b = new RgbColor(r: 0.0, g: 0.0, b: 1.0, a: 1.0);

            $result = $this->resolver->mixSrgb($a, $b, 0.5, premultiplied: true);

            // r = null treated as 0: rPremult=0, rPremult2=0; r=0/0.75=0
            expect($result->r)->toBeCloseTo(0.0)
                ->and($result->a)->toBeCloseTo(0.75);
        });

        it('premultiplied mix produces correct channel values', function (): void {
            $a = new RgbColor(r: 1.0, g: 0.0, b: 0.0, a: 1.0);
            $b = new RgbColor(r: 0.0, g: 0.0, b: 1.0, a: 1.0);

            $result = $this->resolver->mixSrgb($a, $b, 0.5, premultiplied: true);

            // resultAlpha = 1.0; rMixed = 1.0*0.5 + 0.0*0.5 = 0.5; r = 0.5/1.0 = 0.5
            expect($result->r)->toBe(0.5)
                ->and($result->b)->toBe(0.5)
                ->and($result->a)->toBe(1.0);
        });
    });

    describe('mixHsl with none channels', function (): void {
        it('handles none hue in hsl mixing', function (): void {
            $a = new HslColor(h: null, s: 100.0, l: 50.0, a: 1.0);
            $b = new HslColor(h: 120.0, s: 100.0, l: 50.0, a: 1.0);

            $result = $this->resolver->mixHsl($a, $b, 0.5);

            expect($result->h)->toBeCloseTo(120.0);
        });

        it('handles none saturation in hsl mixing', function (): void {
            $a = new HslColor(h: 0.0, s: null, l: 50.0, a: 1.0);
            $b = new HslColor(h: 0.0, s: 100.0, l: 50.0, a: 1.0);

            $result = $this->resolver->mixHsl($a, $b, 0.5);

            expect($result->s)->toBeCloseTo(100.0);
        });

        it('handles none lightness in hsl mixing', function (): void {
            $a = new HslColor(h: 0.0, s: 100.0, l: null, a: 1.0);
            $b = new HslColor(h: 0.0, s: 100.0, l: 50.0, a: 1.0);

            $result = $this->resolver->mixHsl($a, $b, 0.5);

            expect($result->l)->toBeCloseTo(50.0);
        });

        it('handles none alpha in hsl mixing', function (): void {
            $a = new HslColor(h: 0.0, s: 100.0, l: 50.0);
            $b = new HslColor(h: 0.0, s: 100.0, l: 50.0, a: 1.0);

            $result = $this->resolver->mixHsl($a, $b, 0.5);

            expect($result->a)->toBeCloseTo(1.0);
        });
    });

    describe('mixOklch with longer hue method', function (): void {
        it('uses longer path when delta is positive and less than 180', function (): void {
            $a = new OklchColor(l: 50.0, c: 10.0, h: 10.0, a: 1.0);
            $b = new OklchColor(l: 50.0, c: 10.0, h: 50.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5, 'longer');

            // longer: delta = 40 (positive and < 180), so h2 += 360 = 410
            // mid = (10 + 410) / 2 = 210
            expect($result->h)->toBeCloseTo(210.0);
        });

        it('uses longer path when delta is negative and greater than -180', function (): void {
            $a = new OklchColor(l: 50.0, c: 10.0, h: 50.0, a: 1.0);
            $b = new OklchColor(l: 50.0, c: 10.0, h: 10.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5, 'longer');

            // longer: delta = -40 (negative and > -180), so h1 += 360 = 410
            // mid = (410 + 10) / 2 = 210
            expect($result->h)->toBeCloseTo(210.0);
        });

        it('uses longer path with asymmetric weight to distinguish plus/minus variants', function (): void {
            $a = new OklchColor(l: 50.0, c: 10.0, h: 50.0, a: 1.0);
            $b = new OklchColor(l: 50.0, c: 10.0, h: 10.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.75, 'longer');

            // delta = -40, h1 += 360 = 410; mix(410, 10, 0.75) = 410*0.75 + 10*0.25 = 310
            expect($result->h)->toBeCloseTo(310.0);
        });

        it('uses longer path when delta is exactly zero', function (): void {
            $a = new OklchColor(l: 50.0, c: 10.0, h: 10.0, a: 1.0);
            $b = new OklchColor(l: 50.0, c: 10.0, h: 10.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5, 'longer');

            // delta = 0: elseif(delta > -180 && delta <= 0) → true, h1 += 360 → 370
            // mix(370, 10, 0.5) = 190
            expect($result->h)->toBeCloseTo(190.0);
        });

        it('longer path skips both branches when delta is outside range', function (): void {
            $a = new OklchColor(l: 50.0, c: 10.0, h: 10.0, a: 1.0);
            $b = new OklchColor(l: 50.0, c: 10.0, h: 210.0, a: 1.0);

            $result = $this->resolver->mixOklch($a, $b, 0.5, 'longer');

            // delta = 200 (> 180): neither branch taken
            // mix(10, 210, 0.5) = 110
            expect($result->h)->toBeCloseTo(110.0);
        });
    });

    describe('normalizeHue via mixLch', function (): void {
        it('adds exactly 360 to normalize slightly-negative hue', function (): void {
            // decreasing: h1 < h2 is false (-1 < -1 = false), no adjustment
            // mix(-1, -1, 0.5) = -1, normalizeHue(-1) = -1 + 360 = 359
            $a = new LchColor(l: 50.0, c: 10.0, h: -1.0);
            $b = new LchColor(l: 50.0, c: 10.0, h: -1.0);

            $result = $this->resolver->mixLch($a, $b, 0.5, 'decreasing');

            expect($result->h)->toBe(359.0);
        });
    });

    describe('mixLch with hue methods', function (): void {
        it('uses shorter hue method by default', function (): void {
            $a = new LchColor(l: 50.0, c: 10.0, h: 10.0);
            $b = new LchColor(l: 50.0, c: 10.0, h: 50.0);

            $result = $this->resolver->mixLch($a, $b, 0.5);

            expect($result->h)->toBeCloseTo(30.0);
        });

        it('uses longer hue method', function (): void {
            $a = new LchColor(l: 50.0, c: 10.0, h: 10.0);
            $b = new LchColor(l: 50.0, c: 10.0, h: 50.0);

            $result = $this->resolver->mixLch($a, $b, 0.5, 'longer');

            expect($result->h)->toBeCloseTo(210.0);
        });

        it('uses increasing hue method', function (): void {
            $a = new LchColor(l: 50.0, c: 10.0, h: 300.0);
            $b = new LchColor(l: 50.0, c: 10.0, h: 60.0);

            $result = $this->resolver->mixLch($a, $b, 0.5, 'increasing');

            expect($result->h)->toBeCloseTo(0.0, 2.0);
        });

        it('uses decreasing hue method', function (): void {
            $a = new LchColor(l: 50.0, c: 10.0, h: 60.0);
            $b = new LchColor(l: 50.0, c: 10.0, h: 300.0);

            $result = $this->resolver->mixLch($a, $b, 0.5, 'decreasing');

            expect($result->h)->toBeCloseTo(0.0, 2.0);
        });

        it('normalizes negative hue result', function (): void {
            $a = new LchColor(l: 50.0, c: 10.0, h: 10.0);
            $b = new LchColor(l: 50.0, c: 10.0, h: 350.0);

            $result = $this->resolver->mixLch($a, $b, 0.5, 'increasing');

            // increasing: h2 < h1 → h2 += 360 = 710
            // mid = (10 + 710) / 2 = 360 → normalized to 0
            expect($result->h)->toBeGreaterThanOrEqual(0.0)
                ->and($result->h)->toBeLessThan(360.0);
        });

        it('normalizeHue handles negative input via mixLch', function (): void {
            $a = new LchColor(l: 50.0, c: 10.0, h: -100.0);
            $b = new LchColor(l: 50.0, c: 10.0, h: -80.0);

            $result = $this->resolver->mixLch($a, $b, 0.5, 'increasing');

            expect($result->h)->toBeCloseTo(270.0);
        });
    });

    describe('mixLab with none channels', function (): void {
        it('handles none lightness in lab mixing', function (): void {
            $a = new LabColor(l: null, a: 0.0, b: 0.0, alpha: 1.0);
            $b = new LabColor(l: 100.0, a: 0.0, b: 0.0, alpha: 1.0);

            $result = $this->resolver->mixLab($a, $b, 0.5);

            expect($result->l)->toBeCloseTo(100.0);
        });

        it('handles none a channel in lab mixing', function (): void {
            $a = new LabColor(l: 50.0, a: null, b: 0.0, alpha: 1.0);
            $b = new LabColor(l: 50.0, a: 50.0, b: 0.0, alpha: 1.0);

            $result = $this->resolver->mixLab($a, $b, 0.5);

            expect($result->a)->toBeCloseTo(50.0);
        });

        it('handles none b channel in lab mixing', function (): void {
            $a = new LabColor(l: 50.0, a: 0.0, b: null, alpha: 1.0);
            $b = new LabColor(l: 50.0, a: 0.0, b: -50.0, alpha: 1.0);

            $result = $this->resolver->mixLab($a, $b, 0.5);

            expect($result->b)->toBeCloseTo(-50.0);
        });
    });

    describe('mixLch with none channels', function (): void {
        it('handles none lightness in lch mixing', function (): void {
            $a = new LchColor(l: null, c: 10.0, h: 0.0);
            $b = new LchColor(l: 100.0, c: 10.0, h: 0.0);

            $result = $this->resolver->mixLch($a, $b, 0.5);

            expect($result->l)->toBeCloseTo(100.0);
        });

        it('handles none chroma in lch mixing', function (): void {
            $a = new LchColor(l: 50.0, c: null, h: 0.0);
            $b = new LchColor(l: 50.0, c: 60.0, h: 0.0);

            $result = $this->resolver->mixLch($a, $b, 0.5);

            expect($result->c)->toBeCloseTo(60.0);
        });

        it('handles none hue in lch mixing', function (): void {
            $a = new LchColor(l: 50.0, c: 10.0, h: null);
            $b = new LchColor(l: 50.0, c: 10.0, h: 180.0);

            $result = $this->resolver->mixLch($a, $b, 0.5);

            expect($result->h)->toBeCloseTo(180.0);
        });
    });
});
