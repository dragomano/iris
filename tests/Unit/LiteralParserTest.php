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

        expect($rgb)->not->toBeNull()
            ->and($rgb?->r)->toBe(255.0)
            ->and($rgb?->g)->toBe(0.0)
            ->and($rgb?->b)->toBe(0.0)
            ->and($rgb?->a)->toBe(1.0);
    });

    it('converts short hex colors to rgb', function (): void {
        $rgb = $this->converter->toRgb('#abc');

        expect($rgb)->not->toBeNull()
            ->and($rgb?->r)->toBe(170.0)
            ->and($rgb?->g)->toBe(187.0)
            ->and($rgb?->b)->toBe(204.0)
            ->and($rgb?->a)->toBe(1.0);
    });

    it('converts hex colors with alpha to rgb', function (): void {
        $rgb = $this->converter->toRgb('#112233b3');

        expect($rgb)->not->toBeNull()
            ->and($rgb?->r)->toBe(17.0)
            ->and($rgb?->g)->toBe(34.0)
            ->and($rgb?->b)->toBe(51.0)
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
        $context = new RgbColor(r: 100.0, g: 150.0, b: 200.0, a: 0.8);
        $result  = $this->converter->toRgb('currentcolor', $context);

        expect($result)->toBe($context)
            ->and($result->r)->toBe(100.0)
            ->and($result->g)->toBe(150.0)
            ->and($result->b)->toBe(200.0)
            ->and($result->a)->toBe(0.8);
    });

    it('returns null for currentcolor without context', function (): void {
        $result = $this->converter->toRgb('currentcolor', null);

        expect($result)->toBeNull();
    });
});
