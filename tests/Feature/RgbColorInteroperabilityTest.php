<?php

declare(strict_types=1);

use Bugo\Iris\LiteralParser;
use Bugo\Iris\Serializers\CssSerializer;
use Bugo\Iris\Serializers\LiteralSerializer;
use Bugo\Iris\Spaces\RgbColor;

describe('RgbColor interoperability', function (): void {
    beforeEach(function (): void {
        $this->parser            = new LiteralParser();
        $this->cssSerializer     = new CssSerializer();
        $this->literalSerializer = new LiteralSerializer();
    });

    it('keeps parser and literal serializer on the same byte-based contract', function (): void {
        $rgb = $this->parser->toRgb('#ff8000');

        expect($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($this->literalSerializer->serialize($rgb))->toBe('#ff8000');
    });

    it('serializes parser output through CssSerializer using its normalized contract', function (): void {
        $rgb = $this->parser->toRgb('#ff8000');

        expect($rgb)->toBeInstanceOf(RgbColor::class)
            ->and($this->cssSerializer->toCss($rgb))->toBe('rgb(255 255 0)')
            ->and($this->cssSerializer->toHex($rgb))->toBe('#ffff00');
    });

    it('serializes normalized rgb objects consistently in CssSerializer', function (): void {
        $rgb = new RgbColor(r: 1.0, g: 0.5, b: 0.0, a: 1.0);

        expect($this->cssSerializer->toCss($rgb))->toBe('rgb(255 128 0)')
            ->and($this->cssSerializer->toHex($rgb))->toBe('#ff8000');
    });
});
