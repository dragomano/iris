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
        it('converts rgb(255, 0, 0) to hsl(0, 100, 50)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(255.0, 0.0, 0.0, 1.0));

            expect($hsl->h)->toBe(0.0)
                ->and($hsl->s)->toBe(100.0)
                ->and($hsl->l)->toBe(50.0);
        });

        it('converts rgb(0, 255, 0) to hsl(120, 100, 50)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(0.0, 255.0, 0.0, 1.0));

            expect($hsl->h)->toBe(120.0)
                ->and($hsl->s)->toBe(100.0)
                ->and($hsl->l)->toBe(50.0);
        });

        it('converts rgb(0, 0, 255) to hsl(240, 100, 50)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(0.0, 0.0, 255.0, 1.0));

            expect($hsl->h)->toBe(240.0)
                ->and($hsl->s)->toBe(100.0)
                ->and($hsl->l)->toBe(50.0);
        });

        it('converts rgb(0, 0, 0) to hsl(0, 0, 0) — delta = 0 path', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(0.0, 0.0, 0.0, 1.0));

            expect($hsl->h)->toBe(0.0)
                ->and($hsl->s)->toBe(0.0)
                ->and($hsl->l)->toBe(0.0);
        });

        it('converts rgb(255, 255, 255) to hsl(0, 0, 100) — delta = 0 path', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(255.0, 255.0, 255.0, 1.0));

            expect($hsl->h)->toBe(0.0)
                ->and($hsl->s)->toBe(0.0)
                ->and($hsl->l)->toBe(100.0);
        });

        it('converts rgb(128, 128, 128) to hsl(0, 0, ~50.196)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(128.0, 128.0, 128.0, 0.5));

            expect($hsl->h)->toBe(0.0)
                ->and($hsl->s)->toBe(0.0)
                ->and((float) $hsl->l)->toBeCloseTo(50.196, 2)
                ->and($hsl->a)->toBe(0.5);
        });

        it('converts rgb(0, 255, 255) to hsl(180, 100, 50)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(0.0, 255.0, 255.0, 1.0));

            expect($hsl->h)->toBe(180.0)
                ->and($hsl->s)->toBe(100.0)
                ->and($hsl->l)->toBe(50.0);
        });

        it('converts rgb(0, 0, 128) to hsl(240, 100, ~25.098)', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(0.0, 0.0, 128.0, 1.0));

            expect($hsl->h)->toBe(240.0)
                ->and($hsl->s)->toBe(100.0)
                ->and((float) $hsl->l)->toBeCloseTo(25.098, 2);
        });

        it('preserves alpha channel', function (): void {
            $hsl = $this->converter->rgbToHslColor(new RgbColor(255.0, 0.0, 0.0, 0.7));

            expect($hsl->a)->toBe(0.7);
        });
    });

    describe('hslToRgbColor', function (): void {
        it('converts hsl(0, 100%, 50%) to rgb(255, 0, 0)', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(0.0, 100.0, 50.0, 1.0));

            expect(round($rgb->r))->toBe(255.0)
                ->and(round($rgb->g))->toBe(0.0)
                ->and(round($rgb->b))->toBe(0.0);
        });

        it('converts hsl(120, 100%, 50%) to rgb(0, 255, 0)', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(120.0, 100.0, 50.0, 1.0));

            expect(round($rgb->r))->toBe(0.0)
                ->and(round($rgb->g))->toBe(255.0)
                ->and(round($rgb->b))->toBe(0.0);
        });

        it('converts hsl(240, 100%, 50%) to rgb(0, 0, 255)', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(240.0, 100.0, 50.0, 1.0));

            expect(round($rgb->r))->toBe(0.0)
                ->and(round($rgb->g))->toBe(0.0)
                ->and(round($rgb->b))->toBe(255.0);
        });

        it('converts hsl(0, 0%, 100%) to rgb(255, 255, 255)', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(0.0, 0.0, 100.0, 1.0));

            expect(round($rgb->r))->toBe(255.0)
                ->and(round($rgb->g))->toBe(255.0)
                ->and(round($rgb->b))->toBe(255.0);
        });

        it('converts hsl(0, 0%, 0%) to rgb(0, 0, 0)', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(0.0, 0.0, 0.0, 1.0));

            expect(round($rgb->r))->toBe(0.0)
                ->and(round($rgb->g))->toBe(0.0)
                ->and(round($rgb->b))->toBe(0.0);
        });

        it('preserves alpha channel', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(0.0, 100.0, 50.0, 0.5));

            expect($rgb->a)->toBe(0.5);
        });

        it('converts hsl(60, 100%, 50%) to yellow rgb(255, 255, 0)', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(60.0, 100.0, 50.0, 1.0));

            expect(round($rgb->r))->toBe(255.0)
                ->and(round($rgb->g))->toBe(255.0)
                ->and(round($rgb->b))->toBe(0.0);
        });

        it('converts hsl(180, 100%, 50%) to cyan rgb(0, 255, 255)', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(180.0, 100.0, 50.0, 1.0));

            expect(round($rgb->r))->toBe(0.0)
                ->and(round($rgb->g))->toBe(255.0)
                ->and(round($rgb->b))->toBe(255.0);
        });

        it('converts hsl(0, 0%, 50%) to gray rgb(128, 128, 128)', function (): void {
            $rgb = $this->converter->hslToRgbColor(new HslColor(0.0, 0.0, 50.0, 1.0));

            expect(round($rgb->r))->toBe(128.0)
                ->and(round($rgb->g))->toBe(128.0)
                ->and(round($rgb->b))->toBe(128.0);
        });
    });
});
