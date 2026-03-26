<?php

declare(strict_types=1);

use Bugo\Iris\Converters\ModelConverter;
use Bugo\Iris\Spaces\HslColor;

describe('ModelConverter', function (): void {
    beforeEach(function (): void {
        $this->converter = new ModelConverter();
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
