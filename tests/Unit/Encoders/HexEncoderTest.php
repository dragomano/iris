<?php

declare(strict_types=1);

use Bugo\Iris\Encoders\HexEncoder;

describe('HexEncoder', function (): void {
    beforeEach(function (): void {
        $this->encoder = new HexEncoder();
    });

    describe('encodeRgb', function (): void {
        it('encodes rgb(255,0,0) as #ff0000', function (): void {
            expect($this->encoder->encodeRgb(255, 0, 0))->toBe('#ff0000');
        });

        it('encodes rgb(0,255,0) as #00ff00', function (): void {
            expect($this->encoder->encodeRgb(0, 255, 0))->toBe('#00ff00');
        });

        it('encodes rgb(0,0,255) as #0000ff', function (): void {
            expect($this->encoder->encodeRgb(0, 0, 255))->toBe('#0000ff');
        });

        it('encodes rgb(0,0,0) as #000000', function (): void {
            expect($this->encoder->encodeRgb(0, 0, 0))->toBe('#000000');
        });

        it('encodes rgb(255,255,255) as #ffffff', function (): void {
            expect($this->encoder->encodeRgb(255, 255, 255))->toBe('#ffffff');
        });

        it('result starts with #', function (): void {
            $result = $this->encoder->encodeRgb(128, 64, 32);
            expect($result[0])->toBe('#');
        });

        it('result has 7 characters for rgb encoding', function (): void {
            $result = $this->encoder->encodeRgb(128, 64, 32);
            expect(strlen($result))->toBe(7);
        });

        it('encodes rgb(16,32,48) correctly with zero-padded bytes', function (): void {
            expect($this->encoder->encodeRgb(16, 32, 48))->toBe('#102030');
        });
    });

    describe('encodeRgba', function (): void {
        it('encodes rgba(255,0,0,255) as #ff0000ff', function (): void {
            expect($this->encoder->encodeRgba(255, 0, 0, 255))->toBe('#ff0000ff');
        });

        it('encodes rgba(0,0,0,0) as #00000000', function (): void {
            expect($this->encoder->encodeRgba(0, 0, 0, 0))->toBe('#00000000');
        });

        it('encodes rgba(255,255,255,128) as #ffffff80', function (): void {
            expect($this->encoder->encodeRgba(255, 255, 255, 128))->toBe('#ffffff80');
        });

        it('result starts with #', function (): void {
            $result = $this->encoder->encodeRgba(255, 0, 0, 200);
            expect($result[0])->toBe('#');
        });

        it('result has 9 characters for rgba encoding', function (): void {
            $result = $this->encoder->encodeRgba(255, 0, 0, 200);
            expect(strlen($result))->toBe(9);
        });
    });

    describe('toHexByte', function (): void {
        it('encodes 0 as 00', function (): void {
            expect($this->encoder->toHexByte(0))->toBe('00');
        });

        it('encodes 255 as ff', function (): void {
            expect($this->encoder->toHexByte(255))->toBe('ff');
        });

        it('encodes 16 as 10', function (): void {
            expect($this->encoder->toHexByte(16))->toBe('10');
        });

        it('encodes 128 as 80', function (): void {
            expect($this->encoder->toHexByte(128))->toBe('80');
        });

        it('result is always 2 characters long', function (): void {
            expect(strlen($this->encoder->toHexByte(0)))->toBe(2)
                ->and(strlen($this->encoder->toHexByte(15)))->toBe(2)
                ->and(strlen($this->encoder->toHexByte(255)))->toBe(2);
        });
    });
});
