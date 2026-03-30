<?php

declare(strict_types=1);

use Bugo\Iris\Serializers\LiteralSerializer;
use Bugo\Iris\Spaces\RgbColor;

describe('LiteralSerializer', function (): void {
    beforeEach(function (): void {
        $this->serializer = new LiteralSerializer();
    });

    describe('serialize', function (): void {
        it('serializes named colors when available', function (): void {
            expect($this->serializer->serialize(new RgbColor(255.0, 255.0, 255.0)))->toBe('white')
                ->and($this->serializer->serialize(new RgbColor(0.0, 0.0, 0.0)))->toBe('black');
        });

        it('serializes rgb colors to hex', function (): void {
            expect($this->serializer->serialize(new RgbColor(17.0, 34.0, 51.0)))->toBe('#112233');
        });

        it('serializes rgba colors to hex with alpha', function (): void {
            expect($this->serializer->serialize(new RgbColor(17.0, 34.0, 51.0, 0.7)))->toBe('#112233b3');
        });

        it('serializes fully transparent color with alpha byte', function (): void {
            expect($this->serializer->serialize(new RgbColor(255.0, 0.0, 0.0, 0.0)))->toBe('#ff000000');
        });

        it('serializes semi-transparent black correctly', function (): void {
            expect($this->serializer->serialize(new RgbColor(0.0, 0.0, 0.0, 0.5)))->toBe('#00000080');
        });

        it('serializes non-named opaque color to hex without alpha', function (): void {
            expect($this->serializer->serialize(new RgbColor(100.0, 150.0, 200.0)))->toBe('#6496c8');
        });

        it('serializes color with null channels as zero (resolves to black)', function (): void {
            expect($this->serializer->serialize(new RgbColor(null, null, null)))->toBe('black');
        });
    });

    describe('findNamedColor', function (): void {
        it('returns black for 0 0 0 channels with alpha 1.0', function (): void {
            expect($this->serializer->findNamedColor(0, 0, 0, 1.0))->toBe('black');
        });

        it('returns white for 255 255 255 channels with alpha 1.0', function (): void {
            expect($this->serializer->findNamedColor(255, 255, 255, 1.0))->toBe('white');
        });

        it('returns null for non-named color', function (): void {
            expect($this->serializer->findNamedColor(100, 150, 200, 1.0))->toBeNull();
        });

        it('returns null when alpha is not 1.0', function (): void {
            expect($this->serializer->findNamedColor(0, 0, 0, 0.5))->toBeNull()
                ->and($this->serializer->findNamedColor(255, 255, 255, 0.0))->toBeNull();
        });

        it('returns null when red channel does not match', function (): void {
            expect($this->serializer->findNamedColor(1, 0, 0, 1.0))->toBeNull();
        });

        it('returns null when green channel does not match', function (): void {
            expect($this->serializer->findNamedColor(0, 1, 0, 1.0))->toBeNull();
        });

        it('returns null when blue channel does not match', function (): void {
            expect($this->serializer->findNamedColor(0, 0, 1, 1.0))->toBeNull();
        });
    });
});
