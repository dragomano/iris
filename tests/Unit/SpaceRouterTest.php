<?php

declare(strict_types=1);

use Bugo\Iris\Exceptions\UnsupportedColorSpace;
use Bugo\Iris\SpaceRouter;
use Bugo\Iris\Spaces\RgbColor;
use Bugo\Iris\Spaces\XyzColor;

describe('SpaceRouter', function (): void {
    beforeEach(function (): void {
        $this->router = new SpaceRouter();
    });

    it('converts srgb(1.0, 0.0, 0.0) to rgba red', function (): void {
        $rgb = $this->router->convertToRgba('srgb', 1.0, 0.0, 0.0, 1.0);

        expect(round($rgb->r, 4))->toBe(1.0)
            ->and(round($rgb->g, 4))->toBe(0.0)
            ->and(round($rgb->b, 4))->toBe(0.0)
            ->and($rgb->a)->toBe(1.0);
    });

    it('converts srgb(0.0, 1.0, 0.0) to rgba green', function (): void {
        $rgb = $this->router->convertToRgba('srgb', 0.0, 1.0, 0.0, 1.0);

        expect(round($rgb->r, 4))->toBe(0.0)
            ->and(round($rgb->g, 4))->toBe(1.0)
            ->and(round($rgb->b, 4))->toBe(0.0);
    });

    it('converts srgb(0.0, 0.0, 1.0) to rgba blue', function (): void {
        $rgb = $this->router->convertToRgba('srgb', 0.0, 0.0, 1.0, 1.0);

        expect(round($rgb->r, 4))->toBe(0.0)
            ->and(round($rgb->g, 4))->toBe(0.0)
            ->and(round($rgb->b, 4))->toBe(1.0);
    });

    it('converts srgb-linear to rgba', function (): void {
        // linear(1.0, 0.0, 0.0) -> srgb red ≈ 1.0
        $rgb = $this->router->convertToRgba('srgb-linear', 1.0, 0.0, 0.0, 1.0);

        expect(round($rgb->r, 2))->toBe(1.0)
            ->and(round($rgb->g, 2))->toBe(0.0)
            ->and(round($rgb->b, 2))->toBe(0.0);
    });

    it('converts xyz-d65 to rgba', function (): void {
        // XYZ D65 of white: x≈0.9505, y≈1.0, z≈1.089
        $rgb = $this->router->convertToRgba('xyz-d65', 0.9505, 1.0, 1.0890, 1.0);

        expect($rgb->r)->toBeGreaterThan(0.9)
            ->and($rgb->g)->toBeGreaterThan(0.9)
            ->and($rgb->b)->toBeGreaterThan(0.9);
    });

    it('throws UnsupportedColorSpace for unknown space', function (): void {
        expect(fn() => $this->router->convertToRgba('hsl', 0.0, 100.0, 50.0, 1.0))
            ->toThrow(UnsupportedColorSpace::class);
    });

    it('throws UnsupportedColorSpace for completely unknown space name', function (): void {
        expect(fn() => $this->router->convertToRgba('unknown-space', 0.0, 0.0, 0.0, 1.0))
            ->toThrow(UnsupportedColorSpace::class);
    });

    it('convertToXyzD65 for srgb produces correct xyz', function (): void {
        // srgb red (1,0,0) -> XYZ D65: x≈0.4124, y≈0.2126, z≈0.0193
        $xyz = $this->router->convertToXyzD65('srgb', 1.0, 0.0, 0.0);

        expect($xyz->x)->toBeGreaterThan(0.40)
            ->and($xyz->x)->toBeLessThan(0.42)
            ->and($xyz->y)->toBeGreaterThan(0.21)
            ->and($xyz->y)->toBeLessThan(0.22)
            ->and($xyz->z)->toBeGreaterThan(0.01)
            ->and($xyz->z)->toBeLessThan(0.03);
    });

    it('convertToXyzD65 throws UnsupportedColorSpace for unknown space', function (): void {
        expect(fn() => $this->router->convertToXyzD65('unknown', 0.0, 0.0, 0.0))
            ->toThrow(UnsupportedColorSpace::class);
    });

    it('converts srgb white (1, 1, 1) to white RgbColor', function (): void {
        $result = $this->router->convertToRgba('srgb', 1.0, 1.0, 1.0, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->r)->toBeCloseTo(1.0, 0.00001)
            ->and($result->g)->toBeCloseTo(1.0, 0.00001)
            ->and($result->b)->toBeCloseTo(1.0, 0.00001);
    });

    it('converts srgb black (0, 0, 0) to black RgbColor', function (): void {
        $result = $this->router->convertToRgba('srgb', 0.0, 0.0, 0.0, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->r)->toBeCloseTo(0.0, 0.00001)
            ->and($result->g)->toBeCloseTo(0.0, 0.00001)
            ->and($result->b)->toBeCloseTo(0.0, 0.00001);
    });

    it('clamps srgb channel above 1 to 1', function (): void {
        $result = $this->router->convertToRgba('srgb', 1.5, 0.0, 0.0, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->r)->toBeCloseTo(1.0, 0.00001);
    });

    it('converts srgb-linear white to RgbColor white', function (): void {
        $result = $this->router->convertToRgba('srgb-linear', 1.0, 1.0, 1.0, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->r)->toBeCloseTo(1.0, 0.001)
            ->and($result->g)->toBeCloseTo(1.0, 0.001)
            ->and($result->b)->toBeCloseTo(1.0, 0.001);
    });

    it('converts xyz black (0, 0, 0) to black RgbColor', function (): void {
        $result = $this->router->convertToRgba('xyz', 0.0, 0.0, 0.0, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->r)->toBeCloseTo(0.0, 0.0001)
            ->and($result->g)->toBeCloseTo(0.0, 0.0001)
            ->and($result->b)->toBeCloseTo(0.0, 0.0001);
    });

    it('xyz and xyz-d65 produce the same result', function (): void {
        $xyz    = $this->router->convertToRgba('xyz', 0.3, 0.2, 0.1, 1.0);
        $xyzD65 = $this->router->convertToRgba('xyz-d65', 0.3, 0.2, 0.1, 1.0);

        expect($xyz)->toBeInstanceOf(RgbColor::class)
            ->and($xyzD65)->toBeInstanceOf(RgbColor::class)
            ->and($xyz->r)->toBeCloseTo($xyzD65->r, 0.000001)
            ->and($xyz->g)->toBeCloseTo($xyzD65->g, 0.000001)
            ->and($xyz->b)->toBeCloseTo($xyzD65->b, 0.000001);
    });

    it('preserves alpha channel through conversion', function (): void {
        $result = $this->router->convertToRgba('srgb', 1.0, 0.0, 0.0, 0.5);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->a)->toBeCloseTo(0.5, 0.00001);
    });

    it('converts display-p3 to RgbColor without error', function (): void {
        $result = $this->router->convertToRgba('display-p3', 0.5, 0.5, 0.5, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class);
    });

    it('converts display-p3-linear to RgbColor without error', function (): void {
        $result = $this->router->convertToRgba('display-p3-linear', 0.5, 0.5, 0.5, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class);
    });

    it('converts xyz-d50 to RgbColor without error', function (): void {
        $result = $this->router->convertToRgba('xyz-d50', 0.5, 0.5, 0.5, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class);
    });

    it('converts a98-rgb to RgbColor without error', function (): void {
        $result = $this->router->convertToRgba('a98-rgb', 0.5, 0.5, 0.5, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class);
    });

    it('converts prophoto-rgb to RgbColor without error', function (): void {
        $result = $this->router->convertToRgba('prophoto-rgb', 0.5, 0.5, 0.5, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class);
    });

    it('converts rec2020 to RgbColor without error', function (): void {
        $result = $this->router->convertToRgba('rec2020', 0.5, 0.5, 0.5, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class);
    });

    it('convertToXyzD65 returns XyzColor passthrough for xyz-d65', function (): void {
        $result = $this->router->convertToXyzD65('xyz-d65', 0.3, 0.2, 0.1);

        expect($result)->toBeInstanceOf(XyzColor::class)
            ->and($result->x)->toBeCloseTo(0.3, 0.000001)
            ->and($result->y)->toBeCloseTo(0.2, 0.000001)
            ->and($result->z)->toBeCloseTo(0.1, 0.000001);
    });

    it('convertToXyzD65 returns XyzColor passthrough for xyz', function (): void {
        $result = $this->router->convertToXyzD65('xyz', 0.3, 0.2, 0.1);

        expect($result)->toBeInstanceOf(XyzColor::class)
            ->and($result->x)->toBeCloseTo(0.3, 0.000001)
            ->and($result->y)->toBeCloseTo(0.2, 0.000001)
            ->and($result->z)->toBeCloseTo(0.1, 0.000001);
    });

    it('convertToXyzD65 produces same result for xyz and xyz-d65', function (): void {
        $xyz    = $this->router->convertToXyzD65('xyz', 0.3, 0.2, 0.1);
        $xyzD65 = $this->router->convertToXyzD65('xyz-d65', 0.3, 0.2, 0.1);

        expect($xyz->x)->toBeCloseTo($xyzD65->x, 0.000001)
            ->and($xyz->y)->toBeCloseTo($xyzD65->y, 0.000001)
            ->and($xyz->z)->toBeCloseTo($xyzD65->z, 0.000001);
    });

    it('convertToXyzD65 maps srgb black to xyz origin', function (): void {
        $result = $this->router->convertToXyzD65('srgb', 0.0, 0.0, 0.0);

        expect($result)->toBeInstanceOf(XyzColor::class)
            ->and($result->x)->toBeCloseTo(0.0, 0.00001)
            ->and($result->y)->toBeCloseTo(0.0, 0.00001)
            ->and($result->z)->toBeCloseTo(0.0, 0.00001);
    });

    it('convertToXyzD65 converts srgb-linear without error', function (): void {
        $result = $this->router->convertToXyzD65('srgb-linear', 0.5, 0.5, 0.5);

        expect($result)->toBeInstanceOf(XyzColor::class);
    });

    it('convertToXyzD65 converts display-p3 without error', function (): void {
        $result = $this->router->convertToXyzD65('display-p3', 0.5, 0.5, 0.5);

        expect($result)->toBeInstanceOf(XyzColor::class);
    });

    it('convertToXyzD65 converts display-p3-linear without error', function (): void {
        $result = $this->router->convertToXyzD65('display-p3-linear', 0.5, 0.5, 0.5);

        expect($result)->toBeInstanceOf(XyzColor::class);
    });

    it('convertToXyzD65 converts a98-rgb without error', function (): void {
        $result = $this->router->convertToXyzD65('a98-rgb', 0.5, 0.5, 0.5);

        expect($result)->toBeInstanceOf(XyzColor::class);
    });

    it('convertToXyzD65 converts rec2020 without error', function (): void {
        $result = $this->router->convertToXyzD65('rec2020', 0.5, 0.5, 0.5);

        expect($result)->toBeInstanceOf(XyzColor::class);
    });

    it('convertToXyzD65 converts xyz-d50 without error', function (): void {
        $result = $this->router->convertToXyzD65('xyz-d50', 0.5, 0.5, 0.5);

        expect($result)->toBeInstanceOf(XyzColor::class);
    });

    it('convertToXyzD65 converts prophoto-rgb without error', function (): void {
        $result = $this->router->convertToXyzD65('prophoto-rgb', 0.5, 0.5, 0.5);

        expect($result)->toBeInstanceOf(XyzColor::class);
    });

    it('convertToXyzD65 converts lab without error', function (): void {
        $result = $this->router->convertToXyzD65('lab', 50.0, 20.0, -10.0);

        expect($result)->toBeInstanceOf(XyzColor::class);
    });

    it('convertToXyzD65 converts lch without error', function (): void {
        $result = $this->router->convertToXyzD65('lch', 50.0, 30.0, 180.0);

        expect($result)->toBeInstanceOf(XyzColor::class);
    });

    it('convertToXyzD65 converts oklab without error', function (): void {
        $result = $this->router->convertToXyzD65('oklab', 0.6, 0.1, -0.05);

        expect($result)->toBeInstanceOf(XyzColor::class);
    });

    it('convertToXyzD65 converts oklch without error', function (): void {
        $result = $this->router->convertToXyzD65('oklch', 0.6, 0.15, 30.0);

        expect($result)->toBeInstanceOf(XyzColor::class);
    });

    it('converts lab to rgba', function (): void {
        // Lab white: L=100, a=0, b=0
        $result = $this->router->convertToRgba('lab', 100.0, 0.0, 0.0, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->r)->toBeGreaterThan(0.9)
            ->and($result->g)->toBeGreaterThan(0.9)
            ->and($result->b)->toBeGreaterThan(0.9);
    });

    it('converts lch to rgba', function (): void {
        // LCh white: L=100, C=0, H=0
        $result = $this->router->convertToRgba('lch', 100.0, 0.0, 0.0, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class);
    });

    it('converts oklab to rgba', function (): void {
        // Oklab white: L=1.0, a=0, b=0
        $result = $this->router->convertToRgba('oklab', 1.0, 0.0, 0.0, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->r)->toBeGreaterThan(0.9)
            ->and($result->g)->toBeGreaterThan(0.9)
            ->and($result->b)->toBeGreaterThan(0.9);
    });

    it('converts oklch to rgba', function (): void {
        // OkLCh white: L=100, C=0, H=0
        $result = $this->router->convertToRgba('oklch', 100.0, 0.0, 0.0, 1.0);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->r)->toBeGreaterThan(0.9)
            ->and($result->g)->toBeGreaterThan(0.9)
            ->and($result->b)->toBeGreaterThan(0.9);
    });

    it('converts lab with custom opacity', function (): void {
        $result = $this->router->convertToRgba('lab', 50.0, 25.0, -30.0, 0.5);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->a)->toBeCloseTo(0.5, 0.00001);
    });

    it('converts lch with custom opacity', function (): void {
        $result = $this->router->convertToRgba('lch', 50.0, 30.0, 180.0, 0.7);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->a)->toBeCloseTo(0.7, 0.00001);
    });

    it('converts oklab with custom opacity', function (): void {
        $result = $this->router->convertToRgba('oklab', 0.6, 0.1, -0.05, 0.8);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->a)->toBeCloseTo(0.8, 0.00001);
    });

    it('converts oklch with custom opacity', function (): void {
        $result = $this->router->convertToRgba('oklch', 75.0, 0.15, 30.0, 0.6);

        expect($result)->toBeInstanceOf(RgbColor::class)
            ->and($result->a)->toBeCloseTo(0.6, 0.00001);
    });
});
