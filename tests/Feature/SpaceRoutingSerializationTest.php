<?php

declare(strict_types=1);

use Bugo\Iris\Serializers\CssSerializer;
use Bugo\Iris\SpaceRouter;
use Bugo\Iris\Spaces\RgbColor;

describe('space routing serialization', function (): void {
    beforeEach(function (): void {
        $this->router     = new SpaceRouter();
        $this->serializer = new CssSerializer();
    });

    it('routes srgb channels to CSS and hex output', function (): void {
        $rgb = $this->router->convertToRgba('srgb', 1.0, 0.5, 0.0, 0.5);

        expect($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($this->serializer->toCss($rgb))->toBe('rgb(255 128 0 / 0.50)')
            ->and($this->serializer->toHex($rgb))->toBe('#ff800080');
    });

    it('routes srgb channels to xyz d65 values', function (): void {
        $xyz = $this->router->convertToXyzD65('srgb', 1.0, 0.5, 0.0);

        expect($xyz->x)->toBeCloseTo(0.4889, 0.0001)
            ->and($xyz->y)->toBeCloseTo(0.3657, 0.0001)
            ->and($xyz->z)->toBeCloseTo(0.0448, 0.0001);
    });

    it('keeps xyz aliases aligned when routing to rgba', function (): void {
        $xyz = $this->router->convertToRgba('xyz', 0.3, 0.2, 0.1, 1.0);
        $xyzD65 = $this->router->convertToRgba('xyz-d65', 0.3, 0.2, 0.1, 1.0);

        expect($xyz->r)->toBeCloseTo($xyzD65->r, 0.000001)
            ->and($xyz->g)->toBeCloseTo($xyzD65->g, 0.000001)
            ->and($xyz->b)->toBeCloseTo($xyzD65->b, 0.000001)
            ->and($xyz->a)->toBeCloseTo($xyzD65->a, 0.000001);
    });
});
