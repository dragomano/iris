<?php

declare(strict_types=1);

use Bugo\Iris\Encoders\HexShortener;

describe('HexShortener', function (): void {
    beforeEach(function (): void {
        $this->shortener = new HexShortener();
    });

    describe('7-character hex (6-digit RGB)', function (): void {
        it('shortens #aabbcc to #abc', function (): void {
            expect($this->shortener->shorten('#aabbcc'))->toBe('#abc');
        });

        it('shortens #ff0000 to #f00', function (): void {
            expect($this->shortener->shorten('#ff0000'))->toBe('#f00');
        });

        it('shortens #ffffff to #fff', function (): void {
            expect($this->shortener->shorten('#ffffff'))->toBe('#fff');
        });

        it('shortens #000000 to #000', function (): void {
            expect($this->shortener->shorten('#000000'))->toBe('#000');
        });

        it('does not shorten #ff8800 (pairs differ)', function (): void {
            // #ff8800: ff=ok, 88=ok, 00=ok BUT 88 != ff != 00 => each pair is same but pair values differ
            // [1]=f, [2]=f (same), [3]=8, [4]=8 (same), [5]=0, [6]=0 (same) => shortable to #f80
            expect($this->shortener->shorten('#ff8800'))->toBe('#f80');
        });

        it('does not shorten #aabbcd (last pair differs)', function (): void {
            // [5]=c, [6]=d => not same
            expect($this->shortener->shorten('#aabbcd'))->toBe('#aabbcd');
        });

        it('does not shorten #112233 when pairs are same', function (): void {
            expect($this->shortener->shorten('#112233'))->toBe('#123');
        });
    });

    describe('9-character hex (8-digit RGBA)', function (): void {
        it('shortens #aabbccdd to #abcd', function (): void {
            expect($this->shortener->shorten('#aabbccdd'))->toBe('#abcd');
        });

        it('shortens #ff0000ff to #f00f', function (): void {
            expect($this->shortener->shorten('#ff0000ff'))->toBe('#f00f');
        });

        it('shortens #ff8800ff: all pairs same => shortens to #f80f', function (): void {
            // #ff8800ff: [1]f[2]f [3]8[4]8 [5]0[6]0 [7]f[8]f => all pairs same => #f80f
            expect($this->shortener->shorten('#ff8800ff'))->toBe('#f80f');
        });

        it('does not shorten #ff880044 if not all pairs same', function (): void {
            // [1]f[2]f [3]8[4]8 [5]0[6]0 [7]4[8]4 => all pairs same => #f804
            expect($this->shortener->shorten('#ff880044'))->toBe('#f804');
        });

        it('does not shorten #aabbccde (last pair differs)', function (): void {
            // [7]=d, [8]=e => not same
            expect($this->shortener->shorten('#aabbccde'))->toBe('#aabbccde');
        });
    });

    describe('other lengths pass through unchanged', function (): void {
        it('returns 4-char hex unchanged', function (): void {
            expect($this->shortener->shorten('#abc'))->toBe('#abc');
        });

        it('returns 5-char hex unchanged', function (): void {
            expect($this->shortener->shorten('#abcd'))->toBe('#abcd');
        });
    });
})->covers(HexShortener::class);
