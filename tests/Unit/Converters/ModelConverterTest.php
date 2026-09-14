<?php

declare(strict_types=1);

use Bugo\Iris\Converters\ModelConverter;
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\RgbColor;

describe('ModelConverter', function (): void {
    beforeEach(function (): void {
        $this->converter = new ModelConverter();
    });

    describe('rgbToHslColor', function (): void {
        it('converts red to hsl(0, 100, 50)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(1.0, 0.0, 0.0, 1.0));

            expect($hsl->h)->toBe(0.0)
                ->and($hsl->s)->toBe(100.0)
                ->and($hsl->l)->toBe(50.0);
        });

        it('converts green to hsl(120, 100, 50)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(0.0, 1.0, 0.0, 1.0));

            expect($hsl->h)->toBe(120.0)
                ->and($hsl->s)->toBe(100.0)
                ->and($hsl->l)->toBe(50.0);
        });

        it('converts blue to hsl(240, 100, 50)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(0.0, 0.0, 1.0, 1.0));

            expect($hsl->h)->toBe(240.0)
                ->and($hsl->s)->toBe(100.0)
                ->and($hsl->l)->toBe(50.0);
        });

        it('converts black to hsl(0, 0, 0) — delta = 0 path', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(0.0, 0.0, 0.0, 1.0));

            expect($hsl->h)->toBe(0.0)
                ->and($hsl->s)->toBe(0.0)
                ->and($hsl->l)->toBe(0.0);
        });

        it('converts white to hsl(0, 0, 100) — delta = 0 path', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(1.0, 1.0, 1.0, 1.0));

            expect($hsl->h)->toBe(0.0)
                ->and($hsl->s)->toBe(0.0)
                ->and($hsl->l)->toBe(100.0);
        });

        it('converts mid gray to hsl(0, 0, ~50.196)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(128.0 / 255.0, 128.0 / 255.0, 128.0 / 255.0, 0.5));

            expect($hsl->h)->toBe(0.0)
                ->and($hsl->s)->toBe(0.0)
                ->and((float) $hsl->l)->toBeCloseTo(50.196, 2)
                ->and($hsl->a)->toBe(0.5);
        });

        it('converts cyan to hsl(180, 100, 50)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(0.0, 1.0, 1.0, 1.0));

            expect($hsl->h)->toBe(180.0)
                ->and($hsl->s)->toBe(100.0)
                ->and($hsl->l)->toBe(50.0);
        });

        it('converts navy to hsl(240, 100, ~25.098)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(0.0, 0.0, 128.0 / 255.0, 1.0));

            expect($hsl->h)->toBe(240.0)
                ->and($hsl->s)->toBe(100.0)
                ->and((float) $hsl->l)->toBeCloseTo(25.098, 2);
        });

        it('preserves alpha channel', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(1.0, 0.0, 0.0, 0.7));

            expect($hsl->a)->toBe(0.7);
        });

        it('handles out-of-gamut negative channel with L = 0 without division by zero', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(-0.2, 0.2, 0.1, 1.0));

            expect($hsl->l)->toBe(0.0)
                ->and($hsl->s)->toBe(0.0)
                ->and($hsl->h)->toBe(165.0);
        });

        it('handles out-of-gamut channel with L = 1 without division by zero', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(1.2, 0.8, 0.8, 1.0));

            expect($hsl->l)->toBe(100.0)
                ->and($hsl->s)->toBe(0.0)
                ->and($hsl->h)->toBe(0.0);
        });
    });

    describe('hslToRgbColor', function (): void {
        it('converts hsl(0, 100%, 50%) to red', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(0.0, 100.0, 50.0, 1.0));

            expect($rgb->r)->toBe(1.0)
                ->and($rgb->g)->toBe(0.0)
                ->and($rgb->b)->toBe(0.0);
        });

        it('converts hsl(120, 100%, 50%) to green', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(120.0, 100.0, 50.0, 1.0));

            expect($rgb->r)->toBe(0.0)
                ->and($rgb->g)->toBe(1.0)
                ->and($rgb->b)->toBe(0.0);
        });

        it('converts hsl(240, 100%, 50%) to blue', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(240.0, 100.0, 50.0, 1.0));

            expect($rgb->r)->toBe(0.0)
                ->and($rgb->g)->toBe(0.0)
                ->and($rgb->b)->toBe(1.0);
        });

        it('converts hsl(0, 0%, 100%) to white', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(0.0, 0.0, 100.0, 1.0));

            expect($rgb->r)->toBe(1.0)
                ->and($rgb->g)->toBe(1.0)
                ->and($rgb->b)->toBe(1.0);
        });

        it('converts hsl(0, 0%, 0%) to black', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(0.0, 0.0, 0.0, 1.0));

            expect($rgb->r)->toBe(0.0)
                ->and($rgb->g)->toBe(0.0)
                ->and($rgb->b)->toBe(0.0);
        });

        it('preserves alpha channel', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(0.0, 100.0, 50.0, 0.5));

            expect($rgb->a)->toBe(0.5);
        });

        it('converts hsl(60, 100%, 50%) to yellow', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(60.0, 100.0, 50.0, 1.0));

            expect($rgb->r)->toBe(1.0)
                ->and($rgb->g)->toBe(1.0)
                ->and($rgb->b)->toBe(0.0);
        });

        it('converts hsl(180, 100%, 50%) to cyan', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(180.0, 100.0, 50.0, 1.0));

            expect($rgb->r)->toBe(0.0)
                ->and($rgb->g)->toBe(1.0)
                ->and($rgb->b)->toBe(1.0);
        });

        it('converts hsl(0, 0%, 50%) to mid gray', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(0.0, 0.0, 50.0, 1.0));

            expect($rgb->r)->toBeCloseTo(0.5, 0.000001)
                ->and($rgb->g)->toBeCloseTo(0.5, 0.000001)
                ->and($rgb->b)->toBeCloseTo(0.5, 0.000001);
        });

        it('snaps channels that land on a byte boundary', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(0.0, 0.0, 100.0 * (128.0 / 255.0), 1.0));

            expect($rgb->r)->toBe(128.0 / 255.0)
                ->and($rgb->g)->toBe(128.0 / 255.0)
                ->and($rgb->b)->toBe(128.0 / 255.0);
        });
    });
});
