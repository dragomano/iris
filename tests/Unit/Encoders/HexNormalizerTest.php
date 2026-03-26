<?php

declare(strict_types=1);

use Bugo\Iris\Encoders\HexNormalizer;

describe('HexNormalizer', function (): void {
    beforeEach(function (): void {
        $this->normalizer = new HexNormalizer();
    });

    describe('normalize returns null for invalid input', function (): void {
        it('returns null for empty string', function (): void {
            expect($this->normalizer->normalize(''))->toBeNull();
        });

        it('returns null if does not start with #', function (): void {
            expect($this->normalizer->normalize('ff0000'))->toBeNull();
        });

        it('returns null for invalid hex characters', function (): void {
            expect($this->normalizer->normalize('#gggggg'))->toBeNull();
        });

        it('returns null for wrong length (5 chars)', function (): void {
            expect($this->normalizer->normalize('#fffff'))->toBeNull();
        });

        it('returns null for wrong length (7 chars hex part)', function (): void {
            expect($this->normalizer->normalize('#fffffff'))->toBeNull();
        });
    });

    describe('normalize short hex input', function (): void {
        it('normalizes 3-char hex #f00 and shortens it back (passes through shorten)', function (): void {
            // #f00 -> valid 3-char hex, shorten(#f00) -> #f00 (already 4 chars, not matching 7 or 9)
            $result = $this->normalizer->normalize('#f00');
            expect($result)->toBe('#f00');
        });

        it('normalizes 3-char hex #abc correctly', function (): void {
            $result = $this->normalizer->normalize('#abc');
            expect($result)->toBe('#abc');
        });

        it('normalizes 4-char hex #f00f', function (): void {
            $result = $this->normalizer->normalize('#f00f');
            expect($result)->not->toBeNull();
        });
    });

    describe('normalize 6-char hex', function (): void {
        it('normalizes #ff0000 and shortens to #f00', function (): void {
            // #ff0000 -> shorten -> #f00
            $result = $this->normalizer->normalize('#ff0000');
            expect($result)->toBe('#f00');
        });

        it('normalizes #ff8800 shortens to #f80 (all pairs identical)', function (): void {
            // ff, 88, 00 — each pair is identical, so shortens to #f80
            $result = $this->normalizer->normalize('#ff8800');
            expect($result)->toBe('#f80');
        });

        it('normalizes #aabbcc shortens to #abc', function (): void {
            $result = $this->normalizer->normalize('#aabbcc');
            expect($result)->toBe('#abc');
        });

        it('normalizes uppercase hex to lowercase', function (): void {
            $result = $this->normalizer->normalize('#FF0000');
            expect($result)->toBe('#f00');
        });
    });

    describe('normalize 8-char hex', function (): void {
        it('normalizes #ff0000ff and shortens if possible', function (): void {
            // All pairs match => #f00f
            $result = $this->normalizer->normalize('#ff0000ff');
            expect($result)->toBe('#f00f');
        });

        it('normalizes #aabbccdd shortens to #abcd', function (): void {
            $result = $this->normalizer->normalize('#aabbccdd');
            expect($result)->toBe('#abcd');
        });

        it('normalizes #ff880044 shortens to #f804 (all pairs identical)', function (): void {
            // ff, 88, 00, 44 — each pair is identical, so shortens to #f804
            $result = $this->normalizer->normalize('#ff880044');
            expect($result)->toBe('#f804');
        });
    });
});
