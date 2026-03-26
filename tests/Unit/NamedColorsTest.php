<?php

declare(strict_types=1);

use Bugo\Iris\NamedColors;

describe('NamedColors', function (): void {
    describe('toHex()', function (): void {
        it('converts named color to hex without alpha', function (): void {
            expect(NamedColors::toHex('red'))->toBe('#ff0000')
                ->and(NamedColors::toHex('blue'))->toBe('#0000ff')
                ->and(NamedColors::toHex('green'))->toBe('#008000')
                ->and(NamedColors::toHex('white'))->toBe('#ffffff')
                ->and(NamedColors::toHex('black'))->toBe('#000000');
        });

        it('converts named color to hex with alpha', function (): void {
            expect(NamedColors::toHex('transparent'))->toBe('#00000000');
        });

        it('returns null for unknown color', function (): void {
            expect(NamedColors::toHex('unknowncolor'))->toBeNull()
                ->and(NamedColors::toHex(''))->toBeNull();
        });

        it('converts various colors correctly', function (): void {
            expect(NamedColors::toHex('tomato'))->toBe('#ff6347')
                ->and(NamedColors::toHex('aliceblue'))->toBe('#f0f8ff')
                ->and(NamedColors::toHex('yellowgreen'))->toBe('#9acd32');
        });

        it('converts transparent to 8-char hex', function (): void {
            expect(NamedColors::toHex('transparent'))->toBe('#00000000');
        });
    });

    describe('isNamedColor()', function (): void {
        it('returns true for valid named colors', function (): void {
            expect(NamedColors::isNamedColor('red'))->toBeTrue()
                ->and(NamedColors::isNamedColor('tomato'))->toBeTrue()
                ->and(NamedColors::isNamedColor('aliceblue'))->toBeTrue()
                ->and(NamedColors::isNamedColor('transparent'))->toBeTrue();
        });

        it('returns false for invalid color names', function (): void {
            expect(NamedColors::isNamedColor('unknowncolor'))->toBeFalse()
                ->and(NamedColors::isNamedColor(''))->toBeFalse()
                ->and(NamedColors::isNamedColor('#ff0000'))->toBeFalse()
                ->and(NamedColors::isNamedColor('rgb(255,0,0)'))->toBeFalse();
        });

        it('is case-sensitive', function (): void {
            expect(NamedColors::isNamedColor('Red'))->toBeFalse()
                ->and(NamedColors::isNamedColor('TOMATO'))->toBeFalse();
        });
    });

    describe('getNames()', function (): void {
        it('returns array of all color names', function (): void {
            $names = NamedColors::getNames();

            expect($names)->toBeArray()
                ->and($names)->not->toBeEmpty()
                ->and($names)->toContain('red')
                ->and($names)->toContain('blue')
                ->and($names)->toContain('tomato')
                ->and($names)->toContain('transparent');
        });

        it('returns only string values', function (): void {
            $names = NamedColors::getNames();

            foreach ($names as $name) {
                expect($name)->toBeString();
            }
        });

        it('count matches NAMED_RGB count', function (): void {
            $names = NamedColors::getNames();
            expect(count($names))->toBe(count(NamedColors::NAMED_RGB));
        });
    });
});
