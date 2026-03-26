<?php

declare(strict_types=1);

use Bugo\Iris\LiteralParser;
use Bugo\Iris\Serializers\LiteralSerializer;
use Bugo\Iris\Spaces\RgbColor;

describe('literal round-trip', function (): void {
    beforeEach(function (): void {
        $this->parser     = new LiteralParser();
        $this->serializer = new LiteralSerializer();
    });

    it('round-trips hex literals with alpha through RgbColor', function (): void {
        $rgb = $this->parser->toRgb('#112233b3');

        expect($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($this->serializer->serialize($rgb))->toBe('#112233b3');
    });

    it('round-trips named literals through parser and serializer', function (): void {
        $rgb = $this->parser->toRgb('white');

        expect($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($this->serializer->serialize($rgb))->toBe('white');
    });

    it('passes currentcolor context through the literal pipeline', function (): void {
        $context = new RgbColor(r: 0.0, g: 0.0, b: 0.0, a: 1.0);
        $rgb     = $this->parser->toRgb('currentcolor', $context);

        expect($rgb)->toBe($context)
            ->and($this->serializer->serialize($rgb))->toBe('black');
    });
});
