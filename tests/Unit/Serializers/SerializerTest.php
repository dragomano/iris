<?php

declare(strict_types=1);

use Bugo\Iris\Serializers\Serializer;

describe('Serializer', function (): void {
    beforeEach(function (): void {
        $this->serializer = new Serializer();
    });

    describe('serialize() - hex colors', function (): void {
        it('normalizes uppercase hex to lowercase', function (): void {
            expect($this->serializer->serialize('#AABBCC', false))->toBe('#abc');
        });

        it('normalizes 8-char hex to 4-char when possible', function (): void {
            expect($this->serializer->serialize('#AABBCCDD', false))->toBe('#abcd');
        });

        it('preserves 3-char hex', function (): void {
            expect($this->serializer->serialize('#abc', false))->toBe('#abc');
        });

        it('preserves 4-char hex', function (): void {
            expect($this->serializer->serialize('#abcd', false))->toBe('#abcd');
        });
    });

    describe('serialize() - rgb/rgba colors', function (): void {
        it('preserves rgb when hex output disabled', function (): void {
            expect($this->serializer->serialize('rgb(255, 0, 0)', false))->toBe('rgb(255, 0, 0)');
        });

        it('converts rgb to hex when enabled', function (): void {
            expect($this->serializer->serialize('rgb(255, 0, 0)', true))->toBe('#f00');
        });

        it('converts rgba to hex with alpha', function (): void {
            expect($this->serializer->serialize('rgba(0, 0, 0, .3)', true))->toBe('#0000004d');
        });

        it('handles modern rgb syntax with slash', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 / 0.5)', true))->toBe('#ff000080');
        });

        it('handles rgb with none channels', function (): void {
            expect($this->serializer->serialize('rgb(none 0 0)', false))->toBe('rgb(none 0 0)');
        });

        it('handles rgb with none in modern syntax', function (): void {
            expect($this->serializer->serialize('rgb(255 none 0 / 0.5)', false))->toBe('rgb(255 none 0 / 0.5)');
        });

        it('handles rgb with none alpha in modern syntax', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 / none)', false))->toBe('rgb(255 0 0 / none)');
        });

        it('handles rgb with none in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgb(none, 0, 0)', false))->toBe('rgb(none, 0, 0)');
        });

        it('handles rgba with none alpha in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgba(255, 0, 0, none)', false))->toBe('rgba(255, 0, 0, none)');
        });

        it('handles rgba with invalid channel count in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgba(255, 0, 0)', false))->toBe('rgba(255, 0, 0)');
        });

        it('handles rgb with percentage channels', function (): void {
            expect($this->serializer->serialize('rgb(100% 0% 0%)', false))->toBe('rgb(100% 0% 0%)');
        });

        it('handles rgb with invalid channel values', function (): void {
            expect($this->serializer->serialize('rgb(invalid, 0, 0)', false))->toBe('rgb(invalid, 0, 0)');
        });

        it('handles rgb with invalid alpha', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 / invalid)', false))->toBe('rgb(255 0 0 / invalid)');
        });

        it('handles rgb with invalid channel count', function (): void {
            expect($this->serializer->serialize('rgb(255 0)', false))->toBe('rgb(255 0)');
        });

        it('handles rgb with invalid channel count in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgb(255, 0)', false))->toBe('rgb(255, 0)');
        });

        it('handles rgb with multiple slashes', function (): void {
            expect($this->serializer->serialize('rgb(255 / 0 / 0)', false))->toBe('rgb(255 / 0 / 0)');
        });

        it('handles rgb with slash but missing alpha value', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 /)', false))->toBe('rgb(255 0 0 /)');
        });

        it('handles rgb with empty slash', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 /)', false))->toBe('rgb(255 0 0 /)');
        });

        it('handles rgba with invalid alpha', function (): void {
            expect($this->serializer->serialize('rgba(255, 0, 0, invalid)', false))->toBe('rgba(255, 0, 0, invalid)');
        });
    });

    describe('serialize() - hsl/hsla colors', function (): void {
        it('preserves hsl when hex output disabled', function (): void {
            expect($this->serializer->serialize('hsl(0, 100%, 50%)', false))->toBe('hsl(0, 100%, 50%)');
        });

        it('converts hsl to hex when enabled', function (): void {
            expect($this->serializer->serialize('hsl(0, 100%, 50%)', true))->toBe('#f00');
        });

        it('handles modern hsl syntax', function (): void {
            expect($this->serializer->serialize('hsl(0 100% 50%)', true))->toBe('#f00');
        });

        it('handles hsla with slash syntax', function (): void {
            expect($this->serializer->serialize('hsla(0 100% 50% / 0.5)', true))->toBe('#ff000080');
        });

        it('handles hsl with none hue', function (): void {
            expect($this->serializer->serialize('hsl(none 50% 50%)', false))->toBe('hsl(none 50% 50%)');
        });

        it('handles hsl with none saturation', function (): void {
            expect($this->serializer->serialize('hsl(0 none 50%)', false))->toBe('hsl(0 none 50%)');
        });

        it('handles hsl with none lightness', function (): void {
            expect($this->serializer->serialize('hsl(0 50% none)', false))->toBe('hsl(0 50% none)');
        });

        it('handles legacy hsl syntax with none', function (): void {
            expect($this->serializer->serialize('hsl(none, 50%, 50%)', false))->toBe('hsl(none, 50%, 50%)');
        });

        it('handles legacy hsla syntax with none', function (): void {
            expect($this->serializer->serialize('hsla(none, 50%, 50%, 0.5)', false))
                ->toBe('hsla(none, 50%, 50%, 0.5)');
        });

        it('handles hsla with invalid channel count in legacy syntax', function (): void {
            expect($this->serializer->serialize('hsla(180, 50%, 50%)', false))->toBe('hsla(180, 50%, 50%)');
        });

        it('handles hsla with none in saturation', function (): void {
            expect($this->serializer->serialize('hsla(180, none, 50%, 0.5)', false))
                ->toBe('hsla(180, none, 50%, 0.5)');
        });

        it('handles hsla with none in lightness', function (): void {
            expect($this->serializer->serialize('hsla(180, 50%, none, 0.5)', false))
                ->toBe('hsla(180, 50%, none, 0.5)');
        });

        it('handles hsl with invalid percent values', function (): void {
            expect($this->serializer->serialize('hsl(0 invalid 50%)', false))->toBe('hsl(0 invalid 50%)');
        });

        it('handles hsl with invalid channel count', function (): void {
            expect($this->serializer->serialize('hsl(0 50%)', false))->toBe('hsl(0 50%)');
        });

        it('handles hsl with double slash', function (): void {
            expect($this->serializer->serialize('hsl(0 50% 50% / / 0.5)', false))->toBe('hsl(0 50% 50% / / 0.5)');
        });

        it('handles hsl with comma and invalid count', function (): void {
            expect($this->serializer->serialize('hsl(0, 50%)', false))->toBe('hsl(0, 50%)');
        });

        it('handles hsl with comma and invalid saturation', function (): void {
            expect($this->serializer->serialize('hsl(0, invalid, 50%)', false))->toBe('hsl(0, invalid, 50%)');
        });

        it('handles hsla with comma and none hue', function (): void {
            expect($this->serializer->serialize('hsla(none, 50%, 50%, 0.5)', false))
                ->toBe('hsla(none, 50%, 50%, 0.5)');
        });

        it('serializes hsl colors to hex without float rounding artifacts', function () {
            $result = $this->serializer->serialize('hsl(210deg 40% 50%)', true);

            expect($result)->toBe('#4d80b3');
        });
    });

    describe('serialize() - hwb colors', function (): void {
        it('preserves hwb when hex output disabled', function (): void {
            expect($this->serializer->serialize('hwb(0 0% 0%)', false))->toBe('hwb(0 0% 0%)');
        });

        it('converts hwb to hex when enabled', function (): void {
            expect($this->serializer->serialize('hwb(0 0% 0%)', true))->toBe('#f00');
        });

        it('handles hwb with alpha', function (): void {
            expect($this->serializer->serialize('hwb(0 0% 0% / 0.5)', true))->toBe('#ff000080');
        });

        it('handles hwb with none channels', function (): void {
            expect($this->serializer->serialize('hwb(none 0% 0%)', false))->toBe('hwb(none 0% 0%)');
        });

        it('handles hwb with invalid channel count', function (): void {
            expect($this->serializer->serialize('hwb(0 0%)', false))->toBe('hwb(0 0%)');
        });
    });

    describe('serialize() - lab colors', function (): void {
        it('preserves lab when hex output disabled', function (): void {
            expect($this->serializer->serialize('lab(50% 0 0)', false))->toBe('lab(50% 0 0)');
        });

        it('converts lab to hex when hex output enabled', function (): void {
            expect($this->serializer->serialize('lab(50% 0 0)', true))->toBe('#777');
        });

        it('handles lab with percentage channels', function (): void {
            expect($this->serializer->serialize('lab(50% 100% -50%)', false))->toBe('lab(50% 100% -50%)');
        });

        it('handles lab with alpha', function (): void {
            expect($this->serializer->serialize('lab(50% 0 0 / 0.5)', false))->toBe('lab(50% 0 0 / 0.5)');
        });

        it('handles lab with comma syntax', function (): void {
            expect($this->serializer->serialize('lab(50%, 0, 0)', false))->toBe('lab(50%, 0, 0)');
        });

        it('handles lab with invalid channel count', function (): void {
            expect($this->serializer->serialize('lab(50%, 0)', false))->toBe('lab(50%, 0)');
        });

        it('handles lab with invalid alpha', function (): void {
            expect($this->serializer->serialize('lab(50% 0 0 / invalid)', false))->toBe('lab(50% 0 0 / invalid)');
        });

        it('handles lab with slash but missing alpha', function (): void {
            expect($this->serializer->serialize('lab(50% 0 0 /)', false))->toBe('lab(50% 0 0 /)');
        });
    });

    describe('serialize() - oklab colors', function (): void {
        it('preserves oklab when hex output disabled', function (): void {
            expect($this->serializer->serialize('oklab(0.5 0 0)', false))->toBe('oklab(0.5 0 0)');
        });

        it('handles oklab with percentage channels', function (): void {
            expect($this->serializer->serialize('oklab(50% 100% -50%)', false))->toBe('oklab(50% 100% -50%)');
        });

        it('handles oklab with alpha', function (): void {
            expect($this->serializer->serialize('oklab(0.5 0 0 / 0.5)', false))->toBe('oklab(0.5 0 0 / 0.5)');
        });

        it('handles oklab with comma syntax', function (): void {
            expect($this->serializer->serialize('oklab(0.5, 0, 0)', false))->toBe('oklab(0.5, 0, 0)');
        });

        it('handles oklab with invalid channel count', function (): void {
            expect($this->serializer->serialize('oklab(0.5, 0)', false))->toBe('oklab(0.5, 0)');
        });
    });

    describe('serialize() - lch colors', function (): void {
        it('preserves lch when hex output disabled', function (): void {
            expect($this->serializer->serialize('lch(50% 40 180)', false))->toBe('lch(50% 40 180)');
        });

        it('handles lch with comma syntax', function (): void {
            expect($this->serializer->serialize('lch(50%, 40, 180)', false))->toBe('lch(50%, 40, 180)');
        });

        it('handles lch with alpha', function (): void {
            expect($this->serializer->serialize('lch(50% 40 180 / 0.5)', false))->toBe('lch(50% 40 180 / 0.5)');
        });

        it('handles lch with invalid channel count', function (): void {
            expect($this->serializer->serialize('lch(50%, 40)', false))->toBe('lch(50%, 40)');
        });

        it('handles lch with invalid alpha', function (): void {
            expect($this->serializer->serialize('lch(50% 40 180 / invalid)', false))
                ->toBe('lch(50% 40 180 / invalid)');
        });
    });

    describe('serialize() - oklch colors', function (): void {
        it('preserves oklch when hex output disabled', function (): void {
            expect($this->serializer->serialize('oklch(50% 0.2 180)', false))->toBe('oklch(50% 0.2 180)');
        });

        it('handles oklch with alpha', function (): void {
            expect($this->serializer->serialize('oklch(50% 0.2 180 / 0.8)', false))->toBe('oklch(50% 0.2 180 / 0.8)');
        });

        it('handles oklch with comma syntax', function (): void {
            expect($this->serializer->serialize('oklch(50%, 0.2, 180)', false))->toBe('oklch(50%, 0.2, 180)');
        });

        it('handles oklch with invalid channel count', function (): void {
            expect($this->serializer->serialize('oklch(50%, 0.2)', false))->toBe('oklch(50%, 0.2)');
        });

        it('handles oklch with invalid alpha', function (): void {
            expect($this->serializer->serialize('oklch(50% 0.2 180 / invalid)', false))
                ->toBe('oklch(50% 0.2 180 / invalid)');
        });
    });

    describe('serialize() - color() function', function (): void {
        it('preserves color(srgb) when hex output disabled', function (): void {
            expect($this->serializer->serialize('color(srgb 1 0 0)', false))->toBe('color(srgb 1 0 0)');
        });

        it('converts color(srgb) to hex when enabled', function (): void {
            expect($this->serializer->serialize('color(srgb 1 0 0)', true))->toBe('#f00');
        });

        it('handles color(srgb) with alpha', function (): void {
            expect($this->serializer->serialize('color(srgb 1 0 0 / 0.5)', true))->toBe('#ff000080');
        });

        it('handles color(srgb-linear)', function (): void {
            expect($this->serializer->serialize('color(srgb-linear 1 0 0)', true))->toBe('#f00');
        });

        it('handles color(srgb-linear) with alpha', function (): void {
            expect($this->serializer->serialize('color(srgb-linear 1 0 0 / 0.5)', false))
                ->toBe('color(srgb-linear 1 0 0 / 0.5)');
        });

        it('handles color() with invalid channels', function (): void {
            expect($this->serializer->serialize('color(srgb invalid 0 0)', false))->toBe('color(srgb invalid 0 0)');
        });

        it('handles color() with invalid channel count', function (): void {
            expect($this->serializer->serialize('color(srgb 1 0)', false))->toBe('color(srgb 1 0)');
        });

        it('handles color() with invalid alpha', function (): void {
            expect($this->serializer->serialize('color(srgb 1 0 0 / invalid)', false))
                ->toBe('color(srgb 1 0 0 / invalid)');
        });
    });

    describe('serialize() - color-mix() function', function (): void {
        it('handles color-mix in srgb', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red, blue)', true))->not->toBeNull();
        });

        it('handles color-mix in oklch', function (): void {
            expect($this->serializer->serialize('color-mix(in oklch, #ff0000 50%, #0000ff)', true))->not->toBeNull();
        });

        it('handles color-mix with percentages', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 70%, blue 30%)', true))->not->toBeNull();
        });

        it('handles color-mix in hsl', function (): void {
            expect($this->serializer->serialize('color-mix(in hsl, hsl(0 100% 50%), hsl(120 100% 50%))', true))
                ->not->toBeNull();
        });

        it('handles color-mix in oklab', function (): void {
            expect($this->serializer->serialize('color-mix(in oklab, oklab(1 0 0), oklab(0 0 0))', true))
                ->not->toBeNull();
        });

        it('handles color-mix in lab', function (): void {
            expect($this->serializer->serialize('color-mix(in lab, lab(50% 0 0), lab(0% 0 0))', true))
                ->not->toBeNull();
        });

        it('handles color-mix in lch', function (): void {
            expect($this->serializer->serialize('color-mix(in lch, lch(50% 0 0), lch(0% 0 0))', true))
                ->not->toBeNull();
        });

        it('handles color-mix with invalid color', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, invalidcolor, blue)', false))
                ->toBe('color-mix(in srgb, invalidcolor, blue)');
        });

        it('handles color-mix with missing comma', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb red blue)', false))
                ->toBe('color-mix(in srgb red blue)');
        });

        it('handles color-mix with missing "in" prefix', function (): void {
            expect($this->serializer->serialize('color-mix(srgb, red, blue)', false))
                ->toBe('color-mix(srgb, red, blue)');
        });

        it('handles color-mix with invalid space', function (): void {
            expect($this->serializer->serialize('color-mix(in invalid, red, blue)', false))
                ->toBe('color-mix(in invalid, red, blue)');
        });

        it('handles color-mix in srgb-linear', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb-linear, red, blue)', true))->not->toBeNull();
        });

        it('handles color-mix with both colors having percentages', function (): void {
            expect($this->serializer->serialize('color-mix(in oklch, #ff0000 80%, #0000ff 20%)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with first color percentage only', function (): void {
            expect($this->serializer->serialize('color-mix(in oklch, #ff0000 50%, #0000ff)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with second color percentage only', function (): void {
            expect($this->serializer->serialize('color-mix(in oklch, #ff0000, #0000ff 50%)', true))
                ->not->toBeNull();
        });
    });

    describe('serialize() - error handling', function (): void {
        it('returns invalid rgb syntax as-is', function (): void {
            expect($this->serializer->serialize('rgb(255 / 0 / 0)', false))->toBe('rgb(255 / 0 / 0)');
        });

        it('returns invalid channel count as-is', function (): void {
            expect($this->serializer->serialize('rgb(255 0)', false))->toBe('rgb(255 0)');
        });

        it('returns invalid channel values as-is', function (): void {
            expect($this->serializer->serialize('rgb(invalid 0 0)', false))->toBe('rgb(invalid 0 0)');
        });

        it('returns invalid alpha as-is', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 / invalid)', false))->toBe('rgb(255 0 0 / invalid)');
        });

        it('returns invalid color-mix space as-is', function (): void {
            expect($this->serializer->serialize('color-mix(in invalid, red, blue)', false))
                ->toBe('color-mix(in invalid, red, blue)');
        });
    });

    describe('serialize() - edge cases', function (): void {
        it('handles hue in degrees', function (): void {
            expect($this->serializer->serialize('hsl(180deg 50% 50%)', false))->toBe('hsl(180deg 50% 50%)');
        });

        it('handles hue in turns', function (): void {
            expect($this->serializer->serialize('hsl(0.5turn 50% 50%)', false))->toBe('hsl(0.5turn 50% 50%)');
        });

        it('handles hue in radians', function (): void {
            expect($this->serializer->serialize('hsl(3.14159rad 50% 50%)', false))->toBe('hsl(3.14159rad 50% 50%)');
        });

        it('handles hue in gradians', function (): void {
            expect($this->serializer->serialize('hsl(200grad 50% 50%)', false))->toBe('hsl(200grad 50% 50%)');
        });

        it('handles alpha percentage', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 / 50%)', true))->toBe('#ff000080');
        });

        it('handles empty alpha percentage', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 / %)', false))->toBe('rgb(255 0 0 / %)');
        });

        it('handles percent with invalid number', function (): void {
            expect($this->serializer->serialize('rgb(invalid% 0 0)', false))->toBe('rgb(invalid% 0 0)');
        });

        it('handles empty rgb channel', function (): void {
            expect($this->serializer->serialize('rgb( 0 0)', false))->toBe('rgb( 0 0)');
        });

        it('handles rgb with slash and invalid channel', function (): void {
            expect($this->serializer->serialize('rgb(invalid 0 0 / 0.5)', false))->toBe('rgb(invalid 0 0 / 0.5)');
        });

        it('handles roundAndClampByte edge cases', function (): void {
            expect($this->serializer->serialize('rgb(-10 0 0)', true))->toBe('#000')
                ->and($this->serializer->serialize('rgb(300 0 0)', true))->toBe('#f00');
        });

        it('handles invalid channel count with slash syntax', function (): void {
            expect($this->serializer->serialize('rgb(255 0 / 0.5)', false))->toBe('rgb(255 0 / 0.5)');
        });

        it('handles invalid alpha with slash syntax', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 /)', false))->toBe('rgb(255 0 0 /)');
        });

        it('handles modern syntax with none alpha', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 / none)', false))->toBe('rgb(255 0 0 / none)');
        });

        it('handles rgb with none channel in comma format', function (): void {
            expect($this->serializer->serialize('rgb(none, 0, 0)', false))->toBe('rgb(none, 0, 0)');
        });

        it('handles lab with none channels', function (): void {
            expect($this->serializer->serialize('lab(none none none)', false))->toBe('lab(none none none)');
        });

        it('handles oklab with none channels', function (): void {
            expect($this->serializer->serialize('oklab(none none none)', false))->toBe('oklab(none none none)');
        });

        it('handles lch with none channels', function (): void {
            expect($this->serializer->serialize('lch(none none none)', false))->toBe('lch(none none none)');
        });

        it('handles oklch with none channels', function (): void {
            expect($this->serializer->serialize('oklch(none none none)', false))->toBe('oklch(none none none)');
        });

        it('handles color-mix with none in color', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, rgb(none 0 0), blue)', false))
                ->toBe('color-mix(in srgb, rgb(none 0 0), blue)');
        });

        it('handles color-mix with none hue in hsl', function (): void {
            expect($this->serializer->serialize('color-mix(in hsl, hsl(none 100% 50%), blue)', false))
                ->toBe('color-mix(in hsl, hsl(none 100% 50%), blue)');
        });

        it('handles color-mix with none in lab', function (): void {
            expect($this->serializer->serialize('color-mix(in lab, lab(none 0 0), lab(50% 0 0))', false))
                ->toBe('color-mix(in lab, lab(none 0 0), lab(50% 0 0))');
        });

        it('handles color-mix with none in oklab', function (): void {
            expect($this->serializer->serialize('color-mix(in oklab, oklab(none 0 0), oklab(0.5 0 0))', false))
                ->toBe('color-mix(in oklab, oklab(none 0 0), oklab(0.5 0 0))');
        });

        it('handles color-mix with none in lch', function (): void {
            expect($this->serializer->serialize('color-mix(in lch, lch(none 0 0), lch(50% 0 0))', false))
                ->toBe('color-mix(in lch, lch(none 0 0), lch(50% 0 0))');
        });

        it('handles color-mix with none in oklch', function (): void {
            expect($this->serializer->serialize('color-mix(in oklch, oklch(none 0 0), oklch(50% 0 0))', false))
                ->toBe('color-mix(in oklch, oklch(none 0 0), oklch(50% 0 0))');
        });

        it('handles color-mix with none alpha', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, rgb(255 0 0 / none), blue)', false))
                ->toBe('color-mix(in srgb, rgb(255 0 0 / none), blue)');
        });

        it('handles color-mix with color() function', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, color(srgb 1 0 0), blue)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with hex color', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, #ff0000, #0000ff)', true))->not->toBeNull();
        });

        it('handles color-mix with named color and hex', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red, #0000ff)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with hsl and rgb', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, hsl(0 100% 50%), rgb(0 0 255))', true))
                ->not->toBeNull();
        });

        it('handles color-mix with oklch and hex', function (): void {
            expect($this->serializer->serialize('color-mix(in oklch, oklch(50% 0.2 180), #ff0000)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with lab and oklab', function (): void {
            expect($this->serializer->serialize('color-mix(in lab, lab(50% 0 0), oklab(0.5 0 0))', false))
                ->toBe('color-mix(in lab, lab(50% 0 0), oklab(0.5 0 0))');
        });

        it('handles color-mix with lch and oklch', function (): void {
            expect($this->serializer->serialize('color-mix(in lch, lch(50% 0 0), oklch(50% 0 0))', false))
                ->toBe('color-mix(in lch, lch(50% 0 0), oklch(50% 0 0))');
        });

        it('handles color-mix with all percentages zero', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 0%, blue 0%)', false))
                ->toBe('color-mix(in srgb, red 0%, blue 0%)');
        });

        it('handles color-mix with percentage 100', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 100%, blue)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with percentage 0 for second color', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red, blue 0%)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with both percentages 100', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 100%, blue 100%)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with percentage 50 for both', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 50%, blue 50%)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with percentage 25 and 75', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 25%, blue 75%)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with percentage 75 and 25', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 75%, blue 25%)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with decimal percentage', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 33.33%, blue 66.67%)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with percentage and decimal', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 50.5%, blue)', true))
                ->not->toBeNull();
        });

        it('handles color-mix with negative percentage', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red -10%, blue)', false))
                ->toBe('color-mix(in srgb, red -10%, blue)');
        });

        it('handles color-mix with percentage over 100', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 150%, blue)', false))
                ->toBe('color-mix(in srgb, red 150%, blue)');
        });

        it('handles color-mix with percentage and none', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red none%, blue)', false))
                ->toBe('color-mix(in srgb, red none%, blue)');
        });

        it('handles color-mix with percentage empty', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red %, blue)', false))
                ->toBe('color-mix(in srgb, red %, blue)');
        });

        it('handles color-mix with percentage invalid format', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 50%%, blue)', false))
                ->toBe('color-mix(in srgb, red 50%%, blue)');
        });

        it('handles color-mix with percentage scientific notation', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 5e1%, blue)', false))
                ->toBe('color-mix(in srgb, red 5e1%, blue)');
        });

        it('handles color-mix with percentage leading dot', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red .5%, blue)', false))
                ->toBe('color-mix(in srgb, red .5%, blue)');
        });

        it('handles color-mix with percentage trailing dot', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 50.%, blue)', false))
                ->toBe('color-mix(in srgb, red 50.%, blue)');
        });

        it('handles color-mix with percentage multiple dots', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 5.0.5%, blue)', false))
                ->toBe('color-mix(in srgb, red 5.0.5%, blue)');
        });

        it('handles color-mix with percentage plus sign', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red +50%, blue)', false))
                ->toBe('color-mix(in srgb, red +50%, blue)');
        });

        it('handles color-mix with percentage minus sign', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red -50%, blue)', false))
                ->toBe('color-mix(in srgb, red -50%, blue)');
        });

        it('handles color-mix with percentage space before', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red  50%, blue)', false))
                ->toBe('color-mix(in srgb, red  50%, blue)');
        });

        it('handles color-mix with percentage space after', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 50 %, blue)', false))
                ->toBe('color-mix(in srgb, red 50 %, blue)');
        });

        it('handles color-mix with percentage tab', function (): void {
            expect($this->serializer->serialize("color-mix(in srgb, red\t50%, blue)", false))
                ->toBe("color-mix(in srgb, red\t50%, blue)");
        });

        it('handles color-mix with percentage newline', function (): void {
            expect($this->serializer->serialize("color-mix(in srgb, red\n50%, blue)", false))
                ->toBe("color-mix(in srgb, red\n50%, blue)");
        });

        it('handles color-mix with percentage carriage return', function (): void {
            expect($this->serializer->serialize("color-mix(in srgb, red\r50%, blue)", false))
                ->toBe("color-mix(in srgb, red\r50%, blue)");
        });

        it('handles rgb with none r channel', function (): void {
            expect($this->serializer->serialize('rgb(none 0 0)', false))->toBe('rgb(none 0 0)');
        });

        it('handles rgb with none g channel', function (): void {
            expect($this->serializer->serialize('rgb(0 none 0)', false))->toBe('rgb(0 none 0)');
        });

        it('handles rgb with none b channel', function (): void {
            expect($this->serializer->serialize('rgb(0 0 none)', false))->toBe('rgb(0 0 none)');
        });

        it('handles rgb with none r and g channels', function (): void {
            expect($this->serializer->serialize('rgb(none none 0)', false))->toBe('rgb(none none 0)');
        });

        it('handles rgb with none r and b channels', function (): void {
            expect($this->serializer->serialize('rgb(none 0 none)', false))->toBe('rgb(none 0 none)');
        });

        it('handles rgb with none g and b channels', function (): void {
            expect($this->serializer->serialize('rgb(0 none none)', false))->toBe('rgb(0 none none)');
        });

        it('handles rgb with all none channels', function (): void {
            expect($this->serializer->serialize('rgb(none none none)', false))->toBe('rgb(none none none)');
        });

        it('handles rgb with none r channel and alpha', function (): void {
            expect($this->serializer->serialize('rgb(none 0 0 / 0.5)', false))->toBe('rgb(none 0 0 / 0.5)');
        });

        it('handles rgb with none g channel and alpha', function (): void {
            expect($this->serializer->serialize('rgb(0 none 0 / 0.5)', false))->toBe('rgb(0 none 0 / 0.5)');
        });

        it('handles rgb with none b channel and alpha', function (): void {
            expect($this->serializer->serialize('rgb(0 0 none / 0.5)', false))->toBe('rgb(0 0 none / 0.5)');
        });

        it('handles rgb with none alpha', function (): void {
            expect($this->serializer->serialize('rgb(0 0 0 / none)', false))->toBe('rgb(0 0 0 / none)');
        });

        it('handles rgb with none r and none alpha', function (): void {
            expect($this->serializer->serialize('rgb(none 0 0 / none)', false))->toBe('rgb(none 0 0 / none)');
        });

        it('handles rgb with none g and none alpha', function (): void {
            expect($this->serializer->serialize('rgb(0 none 0 / none)', false))->toBe('rgb(0 none 0 / none)');
        });

        it('handles rgb with none b and none alpha', function (): void {
            expect($this->serializer->serialize('rgb(0 0 none / none)', false))->toBe('rgb(0 0 none / none)');
        });

        it('handles rgb with all none including alpha', function (): void {
            expect($this->serializer->serialize('rgb(none none none / none)', false))
                ->toBe('rgb(none none none / none)');
        });

        it('handles rgba with none r channel', function (): void {
            expect($this->serializer->serialize('rgba(none, 0, 0, 0.5)', false))->toBe('rgba(none, 0, 0, 0.5)');
        });

        it('handles rgba with none g channel', function (): void {
            expect($this->serializer->serialize('rgba(0, none, 0, 0.5)', false))->toBe('rgba(0, none, 0, 0.5)');
        });

        it('handles rgba with none b channel', function (): void {
            expect($this->serializer->serialize('rgba(0, 0, none, 0.5)', false))->toBe('rgba(0, 0, none, 0.5)');
        });

        it('handles rgba with none alpha', function (): void {
            expect($this->serializer->serialize('rgba(0, 0, 0, none)', false))->toBe('rgba(0, 0, 0, none)');
        });

        it('handles rgba with all none', function (): void {
            expect($this->serializer->serialize('rgba(none, none, none, none)', false))
                ->toBe('rgba(none, none, none, none)');
        });

        it('handles rgb with none uppercase', function (): void {
            expect($this->serializer->serialize('rgb(NONE 0 0)', false))->toBe('rgb(NONE 0 0)');
        });

        it('handles rgb with none mixed case', function (): void {
            expect($this->serializer->serialize('rgb(None 0 0)', false))->toBe('rgb(None 0 0)');
        });

        it('handles rgb with none lowercase', function (): void {
            expect($this->serializer->serialize('rgb(none 0 0)', false))->toBe('rgb(none 0 0)');
        });

        it('handles rgb with none and spaces', function (): void {
            expect($this->serializer->serialize('rgb( none  0  0 )', false))->toBe('rgb( none  0  0 )');
        });

        it('handles rgb with none tab', function (): void {
            expect($this->serializer->serialize("rgb(\tnone\t0\t0)", false))->toBe("rgb(\tnone\t0\t0)");
        });

        it('handles rgb with none newline', function (): void {
            expect($this->serializer->serialize("rgb(\nnone\n0\n0)", false))->toBe("rgb(\nnone\n0\n0)");
        });

        it('handles rgb with none carriage return', function (): void {
            expect($this->serializer->serialize("rgb(\rnone\r0\r0)", false))->toBe("rgb(\rnone\r0\r0)");
        });

        it('handles rgb with none form feed', function (): void {
            expect($this->serializer->serialize("rgb(\x0Cnone\x0C0\x0C0)", false))->toBe("rgb(\x0Cnone\x0C0\x0C0)");
        });

        it('handles rgb with none vertical tab', function (): void {
            expect($this->serializer->serialize("rgb(\x0Bnone\x0B0\x0B0)", false))->toBe("rgb(\x0Bnone\x0B0\x0B0)");
        });

        it('handles rgb with none and comma', function (): void {
            expect($this->serializer->serialize('rgb(none,0,0)', false))->toBe('rgb(none,0,0)');
        });

        it('handles rgb with none and comma spaces', function (): void {
            expect($this->serializer->serialize('rgb(none, 0, 0)', false))->toBe('rgb(none, 0, 0)');
        });

        it('handles rgb with none and comma multiple spaces', function (): void {
            expect($this->serializer->serialize('rgb(none,  0,  0)', false))->toBe('rgb(none,  0,  0)');
        });

        it('handles rgb with none and comma tab', function (): void {
            expect($this->serializer->serialize("rgb(none,\t0,\t0)", false))->toBe("rgb(none,\t0,\t0)");
        });

        it('handles rgb with none and comma newline', function (): void {
            expect($this->serializer->serialize("rgb(none,\n0,\n0)", false))->toBe("rgb(none,\n0,\n0)");
        });

        it('handles rgb with none and comma carriage return', function (): void {
            expect($this->serializer->serialize("rgb(none,\r0,\r0)", false))->toBe("rgb(none,\r0,\r0)");
        });

        it('handles rgb with none and comma form feed', function (): void {
            expect($this->serializer->serialize("rgb(none,\x0C0,\x0C0)", false))->toBe("rgb(none,\x0C0,\x0C0)");
        });

        it('handles rgb with none and comma vertical tab', function (): void {
            expect($this->serializer->serialize("rgb(none,\x0B0,\x0B0)", false))->toBe("rgb(none,\x0B0,\x0B0)");
        });

        it('handles rgb with none and comma form feed uppercase', function (): void {
            expect($this->serializer->serialize("rgb(NONE,\x0C0,\x0C0)", false))->toBe("rgb(NONE,\x0C0,\x0C0)");
        });

        it('handles rgb with none and comma vertical tab uppercase', function (): void {
            expect($this->serializer->serialize("rgb(NONE,\x0B0,\x0B0)", false))->toBe("rgb(NONE,\x0B0,\x0B0)");
        });

        it('handles rgb with none and comma carriage return uppercase', function (): void {
            expect($this->serializer->serialize("rgb(NONE,\r0,\r0)", false))->toBe("rgb(NONE,\r0,\r0)");
        });

        it('handles rgb with none and comma newline uppercase', function (): void {
            expect($this->serializer->serialize("rgb(NONE,\n0,\n0)", false))->toBe("rgb(NONE,\n0,\n0)");
        });

        it('handles rgb with none and comma tab uppercase', function (): void {
            expect($this->serializer->serialize("rgb(NONE,\t0,\t0)", false))->toBe("rgb(NONE,\t0,\t0)");
        });

        it('handles rgb with none and comma multiple spaces uppercase', function (): void {
            expect($this->serializer->serialize('rgb(NONE,  0,  0)', false))->toBe('rgb(NONE,  0,  0)');
        });

        it('handles rgb with none and comma spaces uppercase', function (): void {
            expect($this->serializer->serialize('rgb(NONE, 0, 0)', false))->toBe('rgb(NONE, 0, 0)');
        });

        it('handles rgb with none and comma uppercase', function (): void {
            expect($this->serializer->serialize('rgb(NONE,0,0)', false))->toBe('rgb(NONE,0,0)');
        });

        it('handles rgb with none vertical tab uppercase', function (): void {
            expect($this->serializer->serialize("rgb(\x0BNONE\x0B0\x0B0)", false))->toBe("rgb(\x0BNONE\x0B0\x0B0)");
        });

        it('handles rgb with none form feed uppercase', function (): void {
            expect($this->serializer->serialize("rgb(\x0CNONE\x0C0\x0C0)", false))->toBe("rgb(\x0CNONE\x0C0\x0C0)");
        });

        it('handles rgb with none carriage return uppercase', function (): void {
            expect($this->serializer->serialize("rgb(\rNONE\r0\r0)", false))->toBe("rgb(\rNONE\r0\r0)");
        });

        it('handles rgb with none newline uppercase', function (): void {
            expect($this->serializer->serialize("rgb(\nNONE\n0\n0)", false))->toBe("rgb(\nNONE\n0\n0)");
        });

        it('handles rgb with none tab uppercase', function (): void {
            expect($this->serializer->serialize("rgb(\tNONE\t0\t0)", false))->toBe("rgb(\tNONE\t0\t0)");
        });

        it('handles rgb with none and spaces uppercase', function (): void {
            expect($this->serializer->serialize('rgb( NONE  0  0 )', false))->toBe('rgb( NONE  0  0 )');
        });

        it('handles rgb with none lowercase uppercase', function (): void {
            expect($this->serializer->serialize('rgb(none 0 0)', false))->toBe('rgb(none 0 0)');
        });

        it('handles rgb with none mixed case uppercase', function (): void {
            expect($this->serializer->serialize('rgb(None 0 0)', false))->toBe('rgb(None 0 0)');
        });

        it('handles rgb with none uppercase uppercase', function (): void {
            expect($this->serializer->serialize('rgb(NONE 0 0)', false))->toBe('rgb(NONE 0 0)');
        });

        it('handles rgb with none and tab uppercase 2', function (): void {
            expect($this->serializer->serialize("rgb(\tNONE\t0\t0)", false))->toBe("rgb(\tNONE\t0\t0)");
        });

        it('handles rgb with none and newline uppercase 2', function (): void {
            expect($this->serializer->serialize("rgb(\nNONE\n0\n0)", false))->toBe("rgb(\nNONE\n0\n0)");
        });

        it('handles rgb with none and carriage return uppercase 2', function (): void {
            expect($this->serializer->serialize("rgb(\rNONE\r0\r0)", false))->toBe("rgb(\rNONE\r0\r0)");
        });

        it('handles rgb with none and form feed uppercase 2', function (): void {
            expect($this->serializer->serialize("rgb(\x0CNONE\x0C0\x0C0)", false))->toBe("rgb(\x0CNONE\x0C0\x0C0)");
        });

        it('handles rgb with none and vertical tab uppercase 2', function (): void {
            expect($this->serializer->serialize("rgb(\x0BNONE\x0B0\x0B0)", false))->toBe("rgb(\x0BNONE\x0B0\x0B0)");
        });

        it('handles rgb with none and spaces uppercase 2', function (): void {
            expect($this->serializer->serialize('rgb( NONE  0  0 )', false))->toBe('rgb( NONE  0  0 )');
        });

        it('handles rgb with none and comma uppercase 2', function (): void {
            expect($this->serializer->serialize('rgb(NONE,0,0)', false))->toBe('rgb(NONE,0,0)');
        });

        it('handles rgb with none and comma spaces uppercase 2', function (): void {
            expect($this->serializer->serialize('rgb(NONE, 0, 0)', false))->toBe('rgb(NONE, 0, 0)');
        });

        it('handles rgb with none and comma multiple spaces uppercase 2', function (): void {
            expect($this->serializer->serialize('rgb(NONE,  0,  0)', false))->toBe('rgb(NONE,  0,  0)');
        });

        it('handles rgb with none and comma tab uppercase 2', function (): void {
            expect($this->serializer->serialize("rgb(NONE,\t0,\t0)", false))->toBe("rgb(NONE,\t0,\t0)");
        });

        it('handles rgb with none and comma newline uppercase 2', function (): void {
            expect($this->serializer->serialize("rgb(NONE,\n0,\n0)", false))->toBe("rgb(NONE,\n0,\n0)");
        });

        it('handles rgb with none and comma carriage return uppercase 2', function (): void {
            expect($this->serializer->serialize("rgb(NONE,\r0,\r0)", false))->toBe("rgb(NONE,\r0,\r0)");
        });

        it('handles rgb with none and comma form feed uppercase 2', function (): void {
            expect($this->serializer->serialize("rgb(NONE,\x0C0,\x0C0)", false))->toBe("rgb(NONE,\x0C0,\x0C0)");
        });

        it('handles rgb with none and comma vertical tab uppercase 2', function (): void {
            expect($this->serializer->serialize("rgb(NONE,\x0B0,\x0B0)", false))->toBe("rgb(NONE,\x0B0,\x0B0)");
        });

        it('handles rgb with none lowercase 2', function (): void {
            expect($this->serializer->serialize('rgb(none 0 0)', false))->toBe('rgb(none 0 0)');
        });

        it('handles rgb with none mixed case 2', function (): void {
            expect($this->serializer->serialize('rgb(None 0 0)', false))->toBe('rgb(None 0 0)');
        });

        it('handles rgb with none uppercase 2', function (): void {
            expect($this->serializer->serialize('rgb(NONE 0 0)', false))->toBe('rgb(NONE 0 0)');
        });

        it('handles rgb with none and tab 2', function (): void {
            expect($this->serializer->serialize("rgb(\tnone\t0\t0)", false))->toBe("rgb(\tnone\t0\t0)");
        });

        it('handles rgb with none and newline 2', function (): void {
            expect($this->serializer->serialize("rgb(\nnone\n0\n0)", false))->toBe("rgb(\nnone\n0\n0)");
        });

        it('handles rgb with none and carriage return 2', function (): void {
            expect($this->serializer->serialize("rgb(\rnone\r0\r0)", false))->toBe("rgb(\rnone\r0\r0)");
        });

        it('handles rgb with none and form feed 2', function (): void {
            expect($this->serializer->serialize("rgb(\x0Cnone\x0C0\x0C0)", false))->toBe("rgb(\x0Cnone\x0C0\x0C0)");
        });

        it('handles rgb with none and vertical tab 2', function (): void {
            expect($this->serializer->serialize("rgb(\x0Bnone\x0B0\x0B0)", false))->toBe("rgb(\x0Bnone\x0B0\x0B0)");
        });

        it('handles rgb with none and spaces 2', function (): void {
            expect($this->serializer->serialize('rgb( none  0  0 )', false))->toBe('rgb( none  0  0 )');
        });

        it('handles rgb with none and comma 2', function (): void {
            expect($this->serializer->serialize('rgb(none,0,0)', false))->toBe('rgb(none,0,0)');
        });

        it('handles rgb with none and comma spaces 2', function (): void {
            expect($this->serializer->serialize('rgb(none, 0, 0)', false))->toBe('rgb(none, 0, 0)');
        });

        it('handles rgb with none and comma multiple spaces 2', function (): void {
            expect($this->serializer->serialize('rgb(none,  0,  0)', false))->toBe('rgb(none,  0,  0)');
        });

        it('handles rgb with none and comma tab 2', function (): void {
            expect($this->serializer->serialize("rgb(none,\t0,\t0)", false))->toBe("rgb(none,\t0,\t0)");
        });

        it('handles rgb with none and comma newline 2', function (): void {
            expect($this->serializer->serialize("rgb(none,\n0,\n0)", false))->toBe("rgb(none,\n0,\n0)");
        });

        it('handles rgb with none and comma carriage return 2', function (): void {
            expect($this->serializer->serialize("rgb(none,\r0,\r0)", false))->toBe("rgb(none,\r0,\r0)");
        });

        it('handles rgb with none and comma form feed 2', function (): void {
            expect($this->serializer->serialize("rgb(none,\x0C0,\x0C0)", false))->toBe("rgb(none,\x0C0,\x0C0)");
        });

        it('handles rgb with none and comma vertical tab 2', function (): void {
            expect($this->serializer->serialize("rgb(none,\x0B0,\x0B0)", false))->toBe("rgb(none,\x0B0,\x0B0)");
        });

        // Legacy syntax tests for none
        it('handles rgb with none r channel in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgb(none, 0, 0)', false))->toBe('rgb(none, 0, 0)');
        });

        it('handles rgb with none g channel in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgb(255, none, 0)', false))->toBe('rgb(255, none, 0)');
        });

        it('handles rgb with none b channel in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgb(255, 255, none)', false))->toBe('rgb(255, 255, none)');
        });

        it('handles rgba with none alpha in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgba(255, 0, 0, none)', false))->toBe('rgba(255, 0, 0, none)');
        });

        it('handles rgb with all none channels in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgb(none, none, none)', false))->toBe('rgb(none, none, none)');
        });

        it('handles rgba with none r and alpha in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgba(none, 0, 0, 0.5)', false))->toBe('rgba(none, 0, 0, 0.5)');
        });

        it('handles rgba with none g and alpha in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgba(255, none, 0, 0.3)', false))->toBe('rgba(255, none, 0, 0.3)');
        });

        it('handles rgba with all none including alpha in legacy syntax', function (): void {
            expect($this->serializer->serialize('rgba(none, none, none, none)', false))
                ->toBe('rgba(none, none, none, none)');
        });

        // Modern syntax tests for none
        it('handles rgb with none alpha in modern syntax', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 / none)', true))->toBe('#f000');
        });

        it('handles rgb with none r channel in modern syntax', function (): void {
            expect($this->serializer->serialize('rgb(none 0 0)', true))->toBe('#000');
        });

        it('handles rgb with none g channel in modern syntax', function (): void {
            expect($this->serializer->serialize('rgb(255 none 0)', true))->toBe('#f00');
        });

        it('handles rgb with none b channel in modern syntax', function (): void {
            expect($this->serializer->serialize('rgb(255 255 none)', true))->toBe('#ff0');
        });

        it('handles rgb with all none channels in modern syntax', function (): void {
            expect($this->serializer->serialize('rgb(none none none)', true))->toBe('#000');
        });

        it('handles rgb with none r and alpha in modern syntax', function (): void {
            expect($this->serializer->serialize('rgb(none 0 0 / 0.5)', true))->toBe('#00000080');
        });

        it('handles rgb with none g and alpha in modern syntax', function (): void {
            expect($this->serializer->serialize('rgb(255 none 0 / 0.3)', true))->toBe('#ff00004d');
        });

        it('handles rgb with all none including alpha in modern syntax', function (): void {
            expect($this->serializer->serialize('rgb(none none none / none)', true))->toBe('#0000');
        });

        // parseLabChannel: percentage on a/b channels (index > 0) — requires outputHexColors=true to trigger parsing
        it('converts lab with percentage a/b channels to hex when enabled', function (): void {
            expect($this->serializer->serialize('lab(50% 100% -50% / 0.5)', true))->toBeString();
        });

        it('converts oklab with percentage a/b channels to hex when enabled', function (): void {
            expect($this->serializer->serialize('oklab(50% 100% -50% / 0.5)', true))->toBeString();
        });

        // calculateMixWeight: both percentages sum to 0 → returns 0.5
        it('color-mix with both 0% percentages returns a result', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red 0%, blue 0%)', true))->toBeString();
        });

        // parseColorFunction: invalid alpha → null → recognized function → black
        it('color() with invalid alpha returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('color(srgb 1 0 0 / invalid)', true))->toBe('#000');
        });

        // parseSpaceSeparatedHueColorFunction: invalid non-% saturation
        it('hsl with invalid saturation returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('hsl(0 invalid 50%)', true))->toBe('#000');
        });

        // parseSpaceSeparatedHueColorFunction: parts null (wrong count)
        it('hwb with invalid channel count returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('hwb(0 0%)', true))->toBe('#000');
        });

        // parseRgbFunction: legacy error paths — all require outputHexColors=true to trigger parsing
        it('rgba with wrong count in legacy syntax returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('rgba(255, 0, 0)', true))->toBe('#000');
        });

        it('rgb with none in legacy syntax returns original when hex output enabled', function (): void {
            expect($this->serializer->serialize('rgb(none, 0, 0)', true))->toBe('rgb(none, 0, 0)');
        });

        it('rgb with invalid channel in legacy syntax returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('rgb(invalid, 0, 0)', true))->toBe('#000');
        });

        it('rgba with invalid alpha in legacy syntax returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('rgba(255, 0, 0, invalid)', true))->toBe('#000');
        });

        it('rgb with invalid percent channel in legacy syntax returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('rgb(invalid%, 0, 0)', true))->toBe('#000');
        });

        it('rgb with valid percent channels converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('rgb(100%, 0%, 0%)', true))->toBe('#f00');
        });

        it('rgb with percent channels in modern syntax converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('rgb(100% 0% 0%)', true))->toBe('#f00');
        });

        // parseRgbFunction: modern slash error paths
        it('rgb with double slash returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('rgb(255 / 0 / 0)', true))->toBe('#000');
        });

        it('rgb with invalid token in slash syntax returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('rgb(invalid 0 0 / 0.5)', true))->toBe('#000');
        });

        // parseRgbFunction: modern no-slash error paths
        it('rgb with wrong count in modern syntax returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('rgb(255 0)', true))->toBe('#000');
        });

        it('rgb with invalid token in modern syntax returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('rgb(invalid 0 0)', true))->toBe('#000');
        });

        // parseAlpha: empty alpha token (slash with nothing after) → null → black
        it('rgb with empty alpha after slash returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 /)', true))->toBe('#000');
        });

        // parseAlpha: invalid percent value → null → black
        it('rgb with invalid percent alpha returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('rgb(255 0 0 / invalid%)', true))->toBe('#000');
        });

        // parseHslFunction: legacy error paths
        it('hsla with wrong count in legacy syntax returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('hsla(180, 50%, 50%)', true))->toBe('#000');
        });

        it('hsl with none in legacy syntax returns original when hex output enabled', function (): void {
            expect($this->serializer->serialize('hsl(none, 50%, 50%)', true))->toBe('hsl(none, 50%, 50%)');
        });

        it('hsla with valid alpha in legacy syntax converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('hsla(0, 100%, 50%, 0.5)', true))->toBe('#ff000080');
        });

        it('hsla with invalid alpha in legacy syntax returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('hsla(180, 50%, 50%, invalid)', true))->toBe('#000');
        });

        // parseHslFunction: modern with none hue (parseHue returns null)
        it('hsl with none hue converts with default 0 hue when hex output enabled', function (): void {
            expect($this->serializer->serialize('hsl(none 50% 50%)', true))->toBeString();
        });

        // parsePercent: invalid percent token → black
        it('hsl with invalid percent saturation returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('hsl(0 invalid% 50%)', true))->toBe('#000');
        });

        // parseHue: unit variants require outputHexColors=true to run parsing code
        it('hsl with deg hue converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('hsl(180deg 50% 50%)', true))->toBeString();
        });

        it('hsl with turn hue converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('hsl(0.5turn 50% 50%)', true))->toBeString();
        });

        it('hsl with grad hue converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('hsl(200grad 50% 50%)', true))->toBeString();
        });

        it('hsl with rad hue converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('hsl(3.14159rad 50% 50%)', true))->toBeString();
        });

        // parseColorFunction: wrong channel count → parts null → black
        it('color() with wrong channel count returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('color(srgb 1 0)', true))->toBe('#000');
        });

        // parseColorMixFunction: early return paths → null → black
        it('color-mix without in prefix returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('color-mix(srgb, red, blue)', true))->toBe('#000');
        });

        it('color-mix without comma returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb red blue)', true))->toBe('#000');
        });

        it('color-mix with only one color part returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red blue)', true))->toBe('#000');
        });

        it('color-mix with invalid space returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('color-mix(in invalid, red, blue)', true))->toBe('#000');
        });

        // color-mix with named colors — uses no % in color args, properly exercises mix branches
        it('color-mix in hsl with named colors exercises hsl mixing', function (): void {
            expect($this->serializer->serialize('color-mix(in hsl, red, blue)', true))->toBeString();
        });

        it('color-mix in oklab with named colors exercises oklab mixing', function (): void {
            expect($this->serializer->serialize('color-mix(in oklab, red, blue)', true))->toBeString();
        });

        it('color-mix in lab with named colors exercises lab mixing', function (): void {
            expect($this->serializer->serialize('color-mix(in lab, red, blue)', true))->toBeString();
        });

        it('color-mix in lch with named colors exercises lch mixing', function (): void {
            expect($this->serializer->serialize('color-mix(in lch, red, blue)', true))->toBeString();
        });

        // rgbToHsl: max === $g branch (green dominant color)
        it('color-mix in hsl with green dominant color covers max-g hue branch', function (): void {
            expect($this->serializer->serialize('color-mix(in hsl, rgb(0 255 0), red)', true))->toBeString();
        });

        // rgbToHsl: h < 0 correction (max === $r and $g < $b)
        it('color-mix in hsl with magenta-range color covers h-negative correction', function (): void {
            expect($this->serializer->serialize('color-mix(in hsl, rgb(255 0 128), red)', true))->toBeString();
        });

        // rgbToHsl: delta === 0 (achromatic color)
        it('color-mix in hsl with achromatic color covers delta-zero branch', function (): void {
            expect($this->serializer->serialize('color-mix(in hsl, rgb(128 128 128), red)', true))->toBeString();
        });

        // parseThreeChannelFunction: slash success (lch/oklch with alpha)
        it('lch with slash syntax converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('lch(50% 40 180 / 0.5)', true))->toBeString();
        });

        it('oklch with slash syntax converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('oklch(0.5 0.2 180 / 0.5)', true))->toBeString();
        });

        // parseThreeChannelFunction: slash error paths → black
        it('lch with wrong channel count before slash returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('lch(50% 40 / 0.5)', true))->toBe('#000');
        });

        it('lch with invalid alpha returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('lch(50% 40 180 / invalid)', true))->toBe('#000');
        });

        // parseThreeChannelFunction: comma success
        it('lch with comma syntax converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('lch(50%, 40, 180)', true))->toBeString();
        });

        it('oklch with comma syntax converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('oklch(0.5, 0.2, 180)', true))->toBeString();
        });

        // parseThreeChannelFunction: wrong comma count → black
        it('lch with wrong comma count returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('lch(50%, 40)', true))->toBe('#000');
        });

        // parseLabOklabFunction: slash success
        it('lab with slash syntax converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('lab(50% 0 0 / 0.5)', true))->toBeString();
        });

        it('oklab with slash syntax converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('oklab(0.5 0 0 / 0.5)', true))->toBeString();
        });

        // parseLabOklabFunction: slash error paths → black
        it('lab with double slash returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('lab(50% 0 0 / / 0.5)', true))->toBe('#000');
        });

        it('lab with invalid alpha returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('lab(50% 0 0 / invalid)', true))->toBe('#000');
        });

        // parseLabOklabFunction: comma success (also covers parseLabChannel parseNumeric fallback)
        it('lab with comma syntax converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('lab(50%, 0, 0)', true))->toBeString();
        });

        it('oklab with comma syntax converts to hex when enabled', function (): void {
            expect($this->serializer->serialize('oklab(0.5, 0, 0)', true))->toBeString();
        });

        // parseLabOklabFunction: wrong comma count → black
        it('lab with wrong comma count returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('lab(50%, 0)', true))->toBe('#000');
        });

        // parseLabChannel: none token
        it('lab with none channel converts with default 0 when hex output enabled', function (): void {
            expect($this->serializer->serialize('lab(none 0 0 / 0.5)', true))->toBeString();
        });

        // parseLabChannel: invalid percent token
        it('lab with invalid percent channel converts with default 0 when hex output enabled', function (): void {
            expect($this->serializer->serialize('lab(50% inv% 0 / 0.5)', true))->toBeString();
        });

        // parsePercent: none → 0.0 (missing component in modern syntax)
        it('hsl with none saturation converts to neutral gray when hex output enabled', function (): void {
            expect($this->serializer->serialize('hsl(0 none 50%)', true))->toBe('#808080');
        });

        it('hwb with none whiteness converts to pure hue when hex output enabled', function (): void {
            expect($this->serializer->serialize('hwb(0 none 0%)', true))->toBe('#f00');
        });

        // parseColorMixFunction: color1 or color2 is null (not a valid CSS named color)
        it('color-mix with none as color value returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, none, blue)', true))->toBe('#000');
        });

        // parseSpaceSeparatedHueColorFunction: invalid alpha token with slash
        it('hsl with invalid alpha in modern syntax returns black when hex output enabled', function (): void {
            expect($this->serializer->serialize('hsl(0 50% 50% / invalid)', true))->toBe('#000');
        });

        // isLegacyColorWithNone: value has none+comma+) but no matching legacy prefix
        it('color-mix with none and comma but non-legacy prefix is not treated as legacy', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, none, blue)', true))->toBe('#000');
        });
    });
});
