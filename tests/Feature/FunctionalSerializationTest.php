<?php

declare(strict_types=1);

use Bugo\Iris\LiteralParser;
use Bugo\Iris\Serializers\LiteralSerializer;
use Bugo\Iris\Serializers\Serializer;
use Bugo\Iris\Spaces\RgbColor;

describe('functional serialization', function (): void {
    beforeEach(function (): void {
        $this->serializer        = new Serializer();
        $this->parser            = new LiteralParser();
        $this->literalSerializer = new LiteralSerializer();
    });

    it('normalizes modern rgb input into a literal that can be parsed and stored', function (): void {
        $normalized = $this->serializer->serialize('rgb(255 0 0 / 0.5)', true);

        $rgb = $this->parser->toRgb($normalized);

        expect($normalized)->toBe('#ff000080')
            ->and($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($this->literalSerializer->serialize($rgb))->toBe('#ff000080');
    });

    it('normalizes hsl input into a literal that round-trips through the literal api', function (): void {
        $normalized = $this->serializer->serialize('hsl(30 100% 50%)', true);

        $rgb = $this->parser->toRgb($normalized);

        expect($normalized)->toBe('#ff8000')
            ->and($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($this->literalSerializer->serialize($rgb))->toBe('#ff8000');
    });

    it('normalizes color function input into a reusable rgba literal', function (): void {
        $normalized = $this->serializer->serialize('color(srgb 1 0.5 0 / 0.5)', true);

        $rgb = $this->parser->toRgb($normalized);

        expect($normalized)->toBe('#ff800080')
            ->and($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($this->literalSerializer->serialize($rgb))->toBe('#ff800080');
    });

    it('normalizes color-mix output into a literal that the parser can consume', function (): void {
        $normalized = $this->serializer->serialize('color-mix(in oklch, red, blue)', true);

        $rgb = $this->parser->toRgb($normalized);

        expect($normalized)->toBe('#ba00c2')
            ->and($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($this->literalSerializer->serialize($rgb))->toBe('#ba00c2');
    });

    it('preserves functional syntax when the caller keeps css output mode', function (): void {
        $preserved = $this->serializer->serialize('rgb(255 0 0 / 0.5)', false);

        expect($preserved)->toBe('rgb(255 0 0 / 0.5)')
            ->and($this->parser->toRgb($preserved))->toBeNull();
    });
});
