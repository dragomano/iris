<?php

declare(strict_types=1);

use Bugo\Iris\Serializers\LiteralSerializer;
use Bugo\Iris\Spaces\RgbColor;

describe('LiteralSerializer', function (): void {
    it('serializes named colors when available', function (): void {
        $serializer = new LiteralSerializer();

        expect($serializer->serialize(new RgbColor(255.0, 255.0, 255.0)))->toBe('white')
            ->and($serializer->serialize(new RgbColor(0.0, 0.0, 0.0)))->toBe('black');
    });

    it('serializes rgb colors to hex', function (): void {
        $serializer = new LiteralSerializer();

        expect($serializer->serialize(new RgbColor(17.0, 34.0, 51.0)))->toBe('#112233');
    });

    it('serializes rgba colors to hex with alpha', function (): void {
        $serializer = new LiteralSerializer();

        expect($serializer->serialize(new RgbColor(17.0, 34.0, 51.0, 0.7)))->toBe('#112233b3');
    });
});
