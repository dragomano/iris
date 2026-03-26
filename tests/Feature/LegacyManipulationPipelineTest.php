<?php

declare(strict_types=1);

use Bugo\Iris\LiteralParser;
use Bugo\Iris\Manipulators\LegacyManipulator;
use Bugo\Iris\Serializers\LiteralSerializer;
use Bugo\Iris\Spaces\RgbColor;

describe('legacy manipulation pipeline', function (): void {
    beforeEach(function (): void {
        $this->parser      = new LiteralParser();
        $this->manipulator = new LegacyManipulator();
        $this->serializer  = new LiteralSerializer();
    });

    it('parses a literal, darkens it and serializes the result', function (): void {
        $rgb = $this->parser->toRgb('#336699');

        expect($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($this->serializer->serialize($this->manipulator->darken($rgb, 20.0)))->toBe('#19334d');
    });

    it('parses a literal, rotates hue and serializes the result', function (): void {
        $rgb = $this->parser->toRgb('#336699');

        expect($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($this->serializer->serialize($this->manipulator->spin($rgb, 180.0)))->toBe('#996633');
    });

    it('mixes two parsed literals and keeps alpha in the serialized result', function (): void {
        $left  = $this->parser->toRgb('#ff000080');
        $right = $this->parser->toRgb('#0000ff');

        expect($left)->toBeInstanceOf(RgbColor::class)
            ->and($right)->toBeInstanceOf(RgbColor::class)
            ->and($this->serializer->serialize($this->manipulator->mix($left, $right, 0.5)))->toBe('#800080c0');
    });
});
