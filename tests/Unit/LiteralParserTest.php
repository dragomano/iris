<?php

declare(strict_types=1);

use Bugo\Iris\LiteralParser;
use Bugo\Iris\Spaces\RgbColor;

describe('LiteralParser', function (): void {
    beforeEach(function () {
        $this->converter = new LiteralParser();
    });

    it('converts named colors to rgb', function (): void {
        $rgb = $this->converter->toRgb('red');

        expect($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($rgb?->r)->toBe(1.0)
            ->and($rgb?->g)->toBe(0.0)
            ->and($rgb?->b)->toBe(0.0)
            ->and($rgb?->a)->toBe(1.0);
    });

    it('converts short hex colors to rgb', function (): void {
        $rgb = $this->converter->toRgb('#abc');

        expect($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($rgb?->r)->toBeCloseTo(170.0 / 255.0, 0.000001)
            ->and($rgb?->g)->toBeCloseTo(187.0 / 255.0, 0.000001)
            ->and($rgb?->b)->toBeCloseTo(204.0 / 255.0, 0.000001)
            ->and($rgb?->a)->toBe(1.0);
    });

    it('converts hex colors with alpha to rgb', function (): void {
        $rgb = $this->converter->toRgb('#112233b3');

        expect($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($rgb?->r)->toBeCloseTo(17.0 / 255.0, 0.000001)
            ->and($rgb?->g)->toBeCloseTo(34.0 / 255.0, 0.000001)
            ->and($rgb?->b)->toBeCloseTo(51.0 / 255.0, 0.000001)
            ->and($rgb?->a)->toBeCloseTo(179 / 255, 0.001);
    });

    it('returns null for unsupported literals', function (): void {
        expect($this->converter->toRgb('rgb(255, 0, 0)'))->toBeNull()
            ->and($this->converter->toRgb('plain-text'))->toBeNull();
    });

    it('returns null for invalid hex color length', function (): void {
        expect($this->converter->toRgb('#12345'))->toBeNull()
            ->and($this->converter->toRgb('#1234567'))->toBeNull();
    });

    it('returns currentColor context when value is currentcolor', function (): void {
        $context = new RgbColor(r: 0.4, g: 0.6, b: 0.8, a: 0.8);
        $result  = $this->converter->toRgb('currentcolor', $context);

        expect($result)->toBe($context)
            ->and($result->r)->toBe(0.4)
            ->and($result->g)->toBe(0.6)
            ->and($result->b)->toBe(0.8)
            ->and($result->a)->toBe(0.8);
    });

    it('returns null for currentcolor without context', function (): void {
        $result = $this->converter->toRgb('currentcolor', null);

        expect($result)->toBeNull();
    });
});
