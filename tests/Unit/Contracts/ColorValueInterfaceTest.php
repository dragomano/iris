<?php

declare(strict_types=1);

use Bugo\Iris\Contracts\ColorValueInterface;
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\HwbColor;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\LchColor;
use Bugo\Iris\Spaces\OklabColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;
use Bugo\Iris\Spaces\XyzColor;

describe('ColorValueInterface implementation', function (): void {
    it('RgbColor implements ColorValueInterface', function (): void {
        $color = new RgbColor(255.0, 128.0, 0.0, 1.0);

        expect($color)->toBeInstanceOf(ColorValueInterface::class)
            ->and($color->getSpace())->toBe('rgb')
            ->and($color->getChannels())->toHaveCount(3)
            ->and($color->getAlpha())->toBeFloat()
            ->and($color->getAlpha())->toBe(1.0);
    });

    it('HslColor implements ColorValueInterface', function (): void {
        $color = new HslColor(120.0, 100.0, 50.0, 1.0);

        expect($color)->toBeInstanceOf(ColorValueInterface::class)
            ->and($color->getSpace())->toBe('hsl')
            ->and($color->getChannels())->toHaveCount(3)
            ->and($color->getAlpha())->toBeFloat()
            ->and($color->getAlpha())->toBe(1.0);
    });

    it('HwbColor implements ColorValueInterface', function (): void {
        $color = new HwbColor(120.0, 0.0, 0.0, 1.0);

        expect($color)->toBeInstanceOf(ColorValueInterface::class)
            ->and($color->getSpace())->toBe('hwb')
            ->and($color->getChannels())->toHaveCount(3)
            ->and($color->getAlpha())->toBeFloat()
            ->and($color->getAlpha())->toBe(1.0);
    });

    it('OklchColor implements ColorValueInterface', function (): void {
        $color = new OklchColor(62.79, 0.2576, 29.23, 1.0);

        expect($color)->toBeInstanceOf(ColorValueInterface::class)
            ->and($color->getSpace())->toBe('oklch')
            ->and($color->getChannels())->toHaveCount(3)
            ->and($color->getAlpha())->toBeFloat()
            ->and($color->getAlpha())->toBe(1.0);
    });

    it('OklabColor implements ColorValueInterface', function (): void {
        $color = new OklabColor(0.6279, 0.2249, 0.1254, 1.0);

        expect($color)->toBeInstanceOf(ColorValueInterface::class)
            ->and($color->getSpace())->toBe('oklab')
            ->and($color->getChannels())->toHaveCount(3)
            ->and($color->getAlpha())->toBeFloat()
            ->and($color->getAlpha())->toBe(1.0);
    });

    it('LabColor implements ColorValueInterface', function (): void {
        $color = new LabColor(53.23, 80.11, 67.22, 1.0);

        expect($color)->toBeInstanceOf(ColorValueInterface::class)
            ->and($color->getSpace())->toBe('lab')
            ->and($color->getChannels())->toHaveCount(3)
            ->and($color->getAlpha())->toBeFloat()
            ->and($color->getAlpha())->toBe(1.0);
    });

    it('LchColor implements ColorValueInterface', function (): void {
        $color = new LchColor(53.23, 104.55, 40.0, 1.0);

        expect($color)->toBeInstanceOf(ColorValueInterface::class)
            ->and($color->getSpace())->toBe('lch')
            ->and($color->getChannels())->toHaveCount(3)
            ->and($color->getAlpha())->toBeFloat()
            ->and($color->getAlpha())->toBe(1.0);
    });

    it('XyzColor implements ColorValueInterface', function (): void {
        $color = new XyzColor(0.4124, 0.2126, 0.0193);

        expect($color)->toBeInstanceOf(ColorValueInterface::class)
            ->and($color->getSpace())->toBe('xyz-d65')
            ->and($color->getChannels())->toHaveCount(3)
            ->and($color->getAlpha())->toBeFloat()
            ->and($color->getAlpha())->toBe(1.0);
    });

    it('RgbColor channels contain correct values', function (): void {
        $color = new RgbColor(255.0, 128.0, 64.0, 0.5);

        [$r, $g, $b] = $color->getChannels();

        expect($r)->toBe(255.0)
            ->and($g)->toBe(128.0)
            ->and($b)->toBe(64.0)
            ->and($color->getAlpha())->toBe(0.5);
    });

    it('HslColor channels contain correct values', function (): void {
        $color = new HslColor(240.0, 50.0, 75.0, 0.8);

        [$h, $s, $l] = $color->getChannels();

        expect($h)->toBe(240.0)
            ->and($s)->toBe(50.0)
            ->and($l)->toBe(75.0)
            ->and($color->getAlpha())->toBe(0.8);
    });

    it('OklchColor channels contain correct values', function (): void {
        $color = new OklchColor(50.0, 0.15, 180.0, 0.7);

        [$l, $c, $h] = $color->getChannels();

        expect($l)->toBe(50.0)
            ->and($c)->toBe(0.15)
            ->and($h)->toBe(180.0)
            ->and($color->getAlpha())->toBe(0.7);
    });
});
