<?php

declare(strict_types=1);

use Bugo\Iris\Converters\NormalizedRgbChannels;
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\HwbColor;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\LchColor;
use Bugo\Iris\Spaces\OklabColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;
use Bugo\Iris\Spaces\XyzColor;

describe('Color space data classes', function (): void {
    describe('RgbColor', function (): void {
        it('stores r, g, b, a channels', function (): void {
            $rgb = new RgbColor(r: 255.0, g: 128.0, b: 0.0, a: 0.5);

            expect($rgb->r)->toBe(255.0)
                ->and($rgb->g)->toBe(128.0)
                ->and($rgb->b)->toBe(0.0)
                ->and($rgb->a)->toBe(0.5);
        });

        it('defaults alpha to 1.0', function (): void {
            $rgb = new RgbColor(r: 0.0, g: 0.0, b: 0.0);

            expect($rgb->a)->toBe(1.0);
        });

        it('rValue/gValue/bValue return channel values when non-null', function (): void {
            $rgb = new RgbColor(r: 255.0, g: 128.0, b: 64.0);

            expect($rgb->rValue())->toBe(255.0)
                ->and($rgb->gValue())->toBe(128.0)
                ->and($rgb->bValue())->toBe(64.0);
        });

        it('rValue/gValue/bValue return 0.0 when channel is null', function (): void {
            $rgb = new RgbColor(r: null, g: null, b: null);

            expect($rgb->rValue())->toBe(0.0)
                ->and($rgb->gValue())->toBe(0.0)
                ->and($rgb->bValue())->toBe(0.0);
        });
    });

    describe('HslColor', function (): void {
        it('stores h, s, l, a channels', function (): void {
            $hsl = new HslColor(h: 120.0, s: 1.0, l: 0.5, a: 1.0);

            expect($hsl->h)->toBe(120.0)
                ->and($hsl->s)->toBe(1.0)
                ->and($hsl->l)->toBe(0.5)
                ->and($hsl->a)->toBe(1.0);
        });

        it('defaults alpha to 1.0', function (): void {
            $hsl = new HslColor(h: 0.0, s: 0.0, l: 0.0);

            expect($hsl->a)->toBe(1.0);
        });

        it('supports null (none) for hue channel', function (): void {
            $hsl = new HslColor(h: null, s: 50.0, l: 25.0);

            expect($hsl->h)->toBeNull()
                ->and($hsl->s)->toBe(50.0)
                ->and($hsl->l)->toBe(25.0);
        });

        it('supports null (none) for all channels', function (): void {
            $hsl = new HslColor(h: null, s: null, l: null);

            expect($hsl->getChannels())->toBe([null, null, null]);
        });

        it('hValue/sValue/lValue return channel values when non-null', function (): void {
            $hsl = new HslColor(h: 120.0, s: 50.0, l: 75.0);

            expect($hsl->hValue())->toBe(120.0)
                ->and($hsl->sValue())->toBe(50.0)
                ->and($hsl->lValue())->toBe(75.0);
        });

        it('hValue/sValue/lValue return 0.0 when channel is null', function (): void {
            $hsl = new HslColor(h: null, s: null, l: null);

            expect($hsl->hValue())->toBe(0.0)
                ->and($hsl->sValue())->toBe(0.0)
                ->and($hsl->lValue())->toBe(0.0);
        });
    });

    describe('HwbColor', function (): void {
        it('stores h, w, b, a channels', function (): void {
            $hwb = new HwbColor(h: 240.0, w: 0.1, b: 0.2, a: 0.8);

            expect($hwb->h)->toBe(240.0)
                ->and($hwb->w)->toBe(0.1)
                ->and($hwb->b)->toBe(0.2)
                ->and($hwb->a)->toBe(0.8);
        });

        it('defaults alpha to 1.0', function (): void {
            $hwb = new HwbColor(h: 0.0, w: 0.0, b: 0.0);

            expect($hwb->a)->toBe(1.0);
        });

        it('hValue/wValue/bValue return channel values when non-null', function (): void {
            $hwb = new HwbColor(h: 240.0, w: 30.0, b: 10.0);

            expect($hwb->hValue())->toBe(240.0)
                ->and($hwb->wValue())->toBe(30.0)
                ->and($hwb->bValue())->toBe(10.0);
        });

        it('hValue/wValue/bValue return 0.0 when channel is null', function (): void {
            $hwb = new HwbColor(h: null, w: null, b: null);

            expect($hwb->hValue())->toBe(0.0)
                ->and($hwb->wValue())->toBe(0.0)
                ->and($hwb->bValue())->toBe(0.0);
        });
    });

    describe('LabColor', function (): void {
        it('stores l, a, b, alpha channels', function (): void {
            $lab = new LabColor(l: 50.0, a: 25.0, b: -30.0, alpha: 0.9);

            expect($lab->l)->toBe(50.0)
                ->and($lab->a)->toBe(25.0)
                ->and($lab->b)->toBe(-30.0)
                ->and($lab->alpha)->toBe(0.9);
        });

        it('defaults alpha to 1.0', function (): void {
            $lab = new LabColor(l: 0.0, a: 0.0, b: 0.0);

            expect($lab->alpha)->toBe(1.0);
        });

        it('lValue/aValue/bValue return channel values when non-null', function (): void {
            $lab = new LabColor(l: 50.0, a: 25.0, b: -30.0);

            expect($lab->lValue())->toBe(50.0)
                ->and($lab->aValue())->toBe(25.0)
                ->and($lab->bValue())->toBe(-30.0);
        });

        it('lValue/aValue/bValue return 0.0 when channel is null', function (): void {
            $lab = new LabColor(l: null, a: null, b: null);

            expect($lab->lValue())->toBe(0.0)
                ->and($lab->aValue())->toBe(0.0)
                ->and($lab->bValue())->toBe(0.0);
        });
    });

    describe('LchColor', function (): void {
        it('stores l, c, h channels', function (): void {
            $lch = new LchColor(l: 70.0, c: 40.0, h: 200.0);

            expect($lch->l)->toBe(70.0)
                ->and($lch->c)->toBe(40.0)
                ->and($lch->h)->toBe(200.0);
        });

        it('lValue/cValue/hValue return channel values when non-null', function (): void {
            $lch = new LchColor(l: 70.0, c: 40.0, h: 200.0);

            expect($lch->lValue())->toBe(70.0)
                ->and($lch->cValue())->toBe(40.0)
                ->and($lch->hValue())->toBe(200.0);
        });

        it('lValue/cValue/hValue return 0.0 when channel is null', function (): void {
            $lch = new LchColor(l: null, c: null, h: null);

            expect($lch->lValue())->toBe(0.0)
                ->and($lch->cValue())->toBe(0.0)
                ->and($lch->hValue())->toBe(0.0);
        });
    });

    describe('OklabColor', function (): void {
        it('stores l, a, b, alpha channels', function (): void {
            $oklab = new OklabColor(l: 0.6, a: 0.1, b: -0.05, alpha: 1.0);

            expect($oklab->l)->toBe(0.6)
                ->and($oklab->a)->toBe(0.1)
                ->and($oklab->b)->toBe(-0.05)
                ->and($oklab->alpha)->toBe(1.0);
        });

        it('defaults alpha to 1.0', function (): void {
            $oklab = new OklabColor(l: 0.0, a: 0.0, b: 0.0);

            expect($oklab->alpha)->toBe(1.0);
        });

        it('lValue/aValue/bValue return channel values when non-null', function (): void {
            $oklab = new OklabColor(l: 0.6, a: 0.1, b: -0.05);

            expect($oklab->lValue())->toBe(0.6)
                ->and($oklab->aValue())->toBe(0.1)
                ->and($oklab->bValue())->toBe(-0.05);
        });

        it('lValue/aValue/bValue return 0.0 when channel is null', function (): void {
            $oklab = new OklabColor(l: null, a: null, b: null);

            expect($oklab->lValue())->toBe(0.0)
                ->and($oklab->aValue())->toBe(0.0)
                ->and($oklab->bValue())->toBe(0.0);
        });
    });

    describe('OklchColor', function (): void {
        it('stores l, c, h, a channels', function (): void {
            $oklch = new OklchColor(l: 75.0, c: 0.15, h: 30.0, a: 1.0);

            expect($oklch->l)->toBe(75.0)
                ->and($oklch->c)->toBe(0.15)
                ->and($oklch->h)->toBe(30.0)
                ->and($oklch->a)->toBe(1.0);
        });

        it('defaults alpha to 1.0', function (): void {
            $oklch = new OklchColor(l: 0.0, c: 0.0, h: 0.0);

            expect($oklch->a)->toBe(1.0);
        });

        it('lValue/cValue/hValue return channel values when non-null', function (): void {
            $oklch = new OklchColor(l: 75.0, c: 0.15, h: 30.0);

            expect($oklch->lValue())->toBe(75.0)
                ->and($oklch->cValue())->toBe(0.15)
                ->and($oklch->hValue())->toBe(30.0);
        });

        it('lValue/cValue/hValue return 0.0 when channel is null', function (): void {
            $oklch = new OklchColor(l: null, c: null, h: null);

            expect($oklch->lValue())->toBe(0.0)
                ->and($oklch->cValue())->toBe(0.0)
                ->and($oklch->hValue())->toBe(0.0);
        });
    });

    describe('XyzColor', function (): void {
        it('stores x, y, z channels', function (): void {
            $xyz = new XyzColor(x: 0.3, y: 0.2, z: 0.5);

            expect($xyz->x)->toBe(0.3)
                ->and($xyz->y)->toBe(0.2)
                ->and($xyz->z)->toBe(0.5);
        });
    });

    describe('NormalizedRgbChannels', function (): void {
        it('stores all channel data', function (): void {
            $channels = new NormalizedRgbChannels(
                r: 1.0,
                g: 0.5,
                b: 0.0,
                a: 1.0,
                max: 1.0,
                min: 0.0,
                delta: 1.0
            );

            expect($channels->r)->toBe(1.0)
                ->and($channels->g)->toBe(0.5)
                ->and($channels->b)->toBe(0.0)
                ->and($channels->a)->toBe(1.0)
                ->and($channels->max)->toBe(1.0)
                ->and($channels->min)->toBe(0.0)
                ->and($channels->delta)->toBe(1.0);
        });
    });
});
