<?php

declare(strict_types=1);

use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Encoders\HexNormalizer;
use Bugo\Iris\LiteralParser;
use Bugo\Iris\Serializers\CssSerializer;
use Bugo\Iris\Serializers\LiteralSerializer;
use Bugo\Iris\Serializers\Serializer;
use Bugo\Iris\Spaces\RgbColor;

/**
 * Guards the contracts that used to be broken by the byte/normalized channel split:
 * a single RgbColor scale, spec-correct percentage references, premultiplied
 * interpolation, and a closed CssSerializer -> Serializer loop.
 */
describe('CSS Color 4 contract regressions', function (): void {
    beforeEach(function (): void {
        $this->converter         = new SpaceConverter();
        $this->cssSerializer     = new CssSerializer();
        $this->literalSerializer = new LiteralSerializer();
        $this->parser            = new LiteralParser();
        $this->serializer        = new Serializer();
        $this->normalizer        = new HexNormalizer();

        // Serializer::serialize() shortens hex output, CssSerializer::toHex() does not
        $this->referenceHex = function (RgbColor $rgb): string {
            $hex = $this->cssSerializer->toHex($rgb);

            return $this->normalizer->normalize($hex) ?? $hex;
        };
    });

    describe('single RgbColor channel scale', function (): void {
        it('serializes an intermediate color identically through both serializers', function (): void {
            $rgb = new RgbColor(r: 200.0 / 255.0, g: 100.0 / 255.0, b: 50.0 / 255.0, a: 1.0);

            expect($this->cssSerializer->toHex($rgb))->toBe('#c86432')
                ->and($this->literalSerializer->serialize($rgb))->toBe('#c86432')
                ->and($this->cssSerializer->toCss($rgb))->toBe('rgb(200 100 50)');
        });

        it('keeps parser output on the same scale the serializers expect', function (): void {
            $rgb = $this->parser->toRgb('#c86432');

            expect($rgb)->toBeInstanceOf(RgbColor::class)
                ->and($this->cssSerializer->toHex($rgb))->toBe('#c86432')
                ->and($this->literalSerializer->serialize($rgb))->toBe('#c86432');
        });

        it('keeps named color parsing on the normalized scale', function (): void {
            $rgb = $this->parser->toRgb('tomato');

            expect($rgb)->toBeInstanceOf(RgbColor::class)
                ->and($this->cssSerializer->toHex($rgb))->toBe('#ff6347');
        });
    });

    describe('rgbToX and XToRgb are inverse functions', function (): void {
        it('round-trips through every typed conversion pair', function (): void {
            $source = $this->parser->toRgb('#ff8000');

            expect($source)->toBeInstanceOf(RgbColor::class);

            /** @var RgbColor $source */
            $roundTrips = [
                'oklch' => $this->converter->oklchToRgb($this->converter->rgbToOklch($source)),
                'oklab' => $this->converter->oklabToRgb($this->converter->rgbToOklab($source)),
                'lab'   => $this->converter->labToRgb($this->converter->rgbToLab($source)),
                'xyzD65' => $this->converter->xyzD65ToRgb($this->converter->rgbToXyzD65($source), 1.0),
                'xyzD50' => $this->converter->xyzD50ToRgb($this->converter->rgbToXyzD50($source), 1.0),
            ];

            foreach ($roundTrips as $restored) {
                expect($restored->rValue())->toBeCloseTo($source->rValue(), 0.000001)
                    ->and($restored->gValue())->toBeCloseTo($source->gValue(), 0.000001)
                    ->and($restored->bValue())->toBeCloseTo($source->bValue(), 0.000001);
            }
        });

        it('round-trips lch while preserving the achromatic hue convention', function (): void {
            $source = $this->parser->toRgb('#4080c0');

            expect($source)->toBeInstanceOf(RgbColor::class);

            /** @var RgbColor $source */
            $restored = $this->converter->lchToRgb($this->converter->rgbToLch($source));

            expect($restored->rValue())->toBeCloseTo($source->rValue(), 0.000001)
                ->and($restored->gValue())->toBeCloseTo($source->gValue(), 0.000001)
                ->and($restored->bValue())->toBeCloseTo($source->bValue(), 0.000001);
        });
    });

    describe('color-mix() agrees across input notations', function (): void {
        it('produces the same result for hex, named and functional inputs', function (): void {
            $spaces = ['srgb', 'srgb-linear', 'hsl', 'oklab', 'oklch', 'lab', 'lch'];

            foreach ($spaces as $space) {
                $fromHex        = $this->serializer->serialize("color-mix(in {$space}, #000000, #ffffff)", true);
                $fromNamed      = $this->serializer->serialize("color-mix(in {$space}, black, white)", true);
                $fromFunctional = $this->serializer->serialize(
                    "color-mix(in {$space}, rgb(0 0 0), rgb(255 255 255))",
                    true,
                );

                expect($fromNamed)->toBe($fromHex)
                    ->and($fromFunctional)->toBe($fromHex);
            }
        });

        it('produces the same result when notations are mixed within one expression', function (): void {
            $expected = $this->serializer->serialize('color-mix(in srgb, red, blue)', true);

            expect($this->serializer->serialize('color-mix(in srgb, #ff0000, #0000ff)', true))->toBe($expected)
                ->and($this->serializer->serialize('color-mix(in srgb, red, #0000ff)', true))->toBe($expected)
                ->and($this->serializer->serialize('color-mix(in srgb, #ff0000, blue)', true))->toBe($expected)
                ->and($this->serializer->serialize('color-mix(in srgb, rgb(255 0 0), blue)', true))->toBe($expected);
        });

        it('matches the low-level channel API for the srgb midpoint', function (): void {
            $reference = ($this->referenceHex)($this->converter->srgbChannelsToRgb(0.5, 0.5, 0.5, 1.0));

            expect($this->serializer->serialize('color-mix(in srgb, black, white)', true))->toBe($reference);
        });

        it('matches the low-level channel API for the oklab midpoint', function (): void {
            $reference = ($this->referenceHex)($this->converter->oklabChannelsToRgb(0.5, 0.0, 0.0, 1.0));

            expect($this->serializer->serialize('color-mix(in oklab, black, white)', true))->toBe($reference)
                ->and($this->serializer->serialize('color-mix(in oklch, black, white)', true))->toBe($reference);
        });

        it('matches the low-level channel API for the lab midpoint', function (): void {
            $reference = ($this->referenceHex)($this->converter->labChannelsToRgb(50.0, 0.0, 0.0, 1.0));

            expect($this->serializer->serialize('color-mix(in lab, black, white)', true))->toBe($reference)
                ->and($this->serializer->serialize('color-mix(in lch, black, white)', true))->toBe($reference);
        });

        it('does not treat a nested channel percentage as the mix weight', function (): void {
            // the 50% inside lab() belongs to the lightness channel, not to the mix weight
            $reference = ($this->referenceHex)($this->converter->labChannelsToRgb(25.0, 0.0, 0.0, 1.0));

            expect($this->serializer->serialize('color-mix(in lab, lab(50% 0 0), lab(0% 0 0))', true))
                ->toBe($reference);
        });
    });

    describe('srgb-linear interpolates in linear light', function (): void {
        it('differs from srgb and matches the linear-light midpoint', function (): void {
            $midpoint = $this->converter->linSrgbChannelsToRgb(
                ($this->converter->linSrgb(1.0) + $this->converter->linSrgb(0.0)) / 2.0,
                0.0,
                ($this->converter->linSrgb(0.0) + $this->converter->linSrgb(1.0)) / 2.0,
                1.0,
            );

            $linear = $this->serializer->serialize('color-mix(in srgb-linear, red, blue)', true);

            expect($linear)->toBe(($this->referenceHex)($midpoint))
                ->and($linear)->not->toBe($this->serializer->serialize('color-mix(in srgb, red, blue)', true));
        });
    });

    describe('percentage channels equal their numeric counterparts', function (): void {
        it('applies the CSS Color 4 percentage reference range per space and channel', function (): void {
            $pairs = [
                ['lab(50% 0 0)', 'lab(50 0 0)'],
                ['lab(50% 50% 0)', 'lab(50 62.5 0)'],
                ['lab(50% 0 -50%)', 'lab(50 0 -62.5)'],
                ['lch(50% 0 0)', 'lch(50 0 0)'],
                ['lch(50 50% 0)', 'lch(50 75 0)'],
                ['oklab(50% 0 0)', 'oklab(0.5 0 0)'],
                ['oklab(0.5 50% 0)', 'oklab(0.5 0.2 0)'],
                ['oklab(0.5 0 -50%)', 'oklab(0.5 0 -0.2)'],
                ['oklch(50% 0 0)', 'oklch(0.5 0 0)'],
                ['oklch(0.5 50% 0)', 'oklch(0.5 0.2 0)'],
            ];

            foreach ($pairs as [$percentForm, $numberForm]) {
                expect($this->serializer->serialize($percentForm, true))
                    ->toBe($this->serializer->serialize($numberForm, true));
            }
        });
    });

    describe('CssSerializer output is readable by Serializer', function (): void {
        it('closes the loop for every modern color space', function (): void {
            $source = $this->parser->toRgb('#ff8000');

            expect($source)->toBeInstanceOf(RgbColor::class);

            /** @var RgbColor $source */
            $colors = [
                $this->converter->rgbToLab($source),
                $this->converter->rgbToLch($source),
                $this->converter->rgbToOklab($source),
                $this->converter->rgbToOklch($source),
            ];

            foreach ($colors as $color) {
                $css = $this->cssSerializer->toCss($color);

                expect($this->serializer->serialize($css, true))->toBe('#ff8000');
            }
        });

        it('emits a percentage lightness for oklab and oklch', function (): void {
            $source = $this->parser->toRgb('#ff8000');

            expect($source)->toBeInstanceOf(RgbColor::class);

            /** @var RgbColor $source */
            expect($this->cssSerializer->toCss($this->converter->rgbToOklab($source)))->toStartWith('oklab(73.1')
                ->and($this->cssSerializer->toCss($this->converter->rgbToOklab($source)))->toContain('%')
                ->and($this->cssSerializer->toCss($this->converter->rgbToOklch($source)))->toContain('%');
        });
    });

    describe('alpha is premultiplied during interpolation', function (): void {
        it('premultiplies srgb channels when alpha differs', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, rgb(255 0 0 / 0.5), blue)', true))
                ->toBe('#5500aabf');
        });

        it('leaves fully opaque mixes untouched', function (): void {
            expect($this->serializer->serialize('color-mix(in srgb, red, blue)', true))->toBe('#800080');
        });

        it('keeps constant-color transitions unaffected by premultiplication', function (): void {
            // CSS Color 4: premultiplication changes nothing when only alpha differs
            expect($this->serializer->serialize('color-mix(in srgb, rgb(255 0 0 / 1), rgb(255 0 0 / 0))', true))
                ->toBe('#ff000080');
        });
    });
});
