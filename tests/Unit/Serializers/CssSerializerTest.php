<?php

declare(strict_types=1);

use Bugo\Iris\Contracts\ColorValueInterface;
use Bugo\Iris\Serializers\CssSerializer;
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\HwbColor;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\LchColor;
use Bugo\Iris\Spaces\OklabColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;
use Bugo\Iris\Spaces\XyzColor;

describe('CssSerializer', function (): void {
    beforeEach(function (): void {
        $this->serializer = new CssSerializer();
    });

    describe('toCss', function (): void {
        it('converts RgbColor to css string', function (): void {
            $rgb = new RgbColor(r: 1.0, g: 0.5, b: 0.0, a: 1.0);
            $result = $this->serializer->toCss($rgb);

            expect($result)->toBe('rgb(255 128 0)');
        });

        it('converts RgbColor with alpha to css string', function (): void {
            $rgb = new RgbColor(r: 1.0, g: 0.0, b: 0.0, a: 0.5);
            $result = $this->serializer->toCss($rgb);

            expect($result)->toBe('rgb(255 0 0 / 0.50)');
        });

        it('converts RgbColor to hex when useHex is true', function (): void {
            $rgb = new RgbColor(r: 1.0, g: 0.0, b: 0.0, a: 1.0);
            $result = $this->serializer->toCss($rgb, useHex: true);

            expect($result)->toBe('#ff0000');
        });

        it('converts RgbColor with null channels', function (): void {
            $rgb = new RgbColor(r: null, g: 0.5, b: 0.0, a: 1.0);
            $result = $this->serializer->toCss($rgb);

            expect($result)->toBe('rgb(0 128 0)');
        });

        it('converts HslColor to css string', function (): void {
            $hsl = new HslColor(h: 180.0, s: 100.0, l: 50.0, a: 1.0);
            $result = $this->serializer->toCss($hsl);

            expect($result)->toBe('hsl(180 100% 50%)');
        });

        it('converts HslColor with alpha to css string', function (): void {
            $hsl = new HslColor(h: 180.0, s: 100.0, l: 50.0, a: 0.8);
            $result = $this->serializer->toCss($hsl);

            expect($result)->toBe('hsl(180 100% 50% / 0.80)');
        });

        it('converts HwbColor to css string', function (): void {
            $hwb = new HwbColor(h: 180.0, w: 20.0, b: 30.0, a: 1.0);
            $result = $this->serializer->toCss($hwb);

            expect($result)->toBe('hwb(180 20% 30%)');
        });

        it('converts HwbColor with alpha to css string', function (): void {
            $hwb = new HwbColor(h: 180.0, w: 20.0, b: 30.0, a: 0.7);
            $result = $this->serializer->toCss($hwb);

            expect($result)->toBe('hwb(180 20% 30% / 0.70)');
        });

        it('converts LabColor to css string', function (): void {
            $lab = new LabColor(l: 50.0, a: 25.0, b: -30.0, alpha: 1.0);
            $result = $this->serializer->toCss($lab);

            expect($result)->toBe('lab(50% 25 -30)');
        });

        it('converts LabColor with alpha to css string', function (): void {
            $lab = new LabColor(l: 50.0, a: 25.0, b: -30.0, alpha: 0.9);
            $result = $this->serializer->toCss($lab);

            expect($result)->toBe('lab(50% 25 -30 / 0.90)');
        });

        it('converts LchColor to css string', function (): void {
            $lch = new LchColor(l: 70.0, c: 40.0, h: 200.0, alpha: 1.0);
            $result = $this->serializer->toCss($lch);

            expect($result)->toBe('lch(70% 40 200)');
        });

        it('converts LchColor with alpha to css string', function (): void {
            $lch = new LchColor(l: 70.0, c: 40.0, h: 200.0, alpha: 0.85);
            $result = $this->serializer->toCss($lch);

            expect($result)->toBe('lch(70% 40 200 / 0.85)');
        });

        it('converts OklabColor to css string', function (): void {
            $oklab = new OklabColor(l: 0.6, a: 0.1, b: -0.05, alpha: 1.0);
            $result = $this->serializer->toCss($oklab);

            expect($result)->toBe('oklab(0.6 0.1 -0.05)');
        });

        it('converts OklabColor with alpha to css string', function (): void {
            $oklab = new OklabColor(l: 0.6, a: 0.1, b: -0.05, alpha: 0.75);
            $result = $this->serializer->toCss($oklab);

            expect($result)->toBe('oklab(0.6 0.1 -0.05 / 0.75)');
        });

        it('converts OklchColor to css string', function (): void {
            $oklch = new OklchColor(l: 75.0, c: 0.15, h: 30.0, a: 1.0);
            $result = $this->serializer->toCss($oklch);

            expect($result)->toBe('oklch(75 0.15 30)');
        });

        it('converts OklchColor with alpha to css string', function (): void {
            $oklch = new OklchColor(l: 75.0, c: 0.15, h: 30.0, a: 0.6);
            $result = $this->serializer->toCss($oklch);

            expect($result)->toBe('oklch(75 0.15 30 / 0.60)');
        });

        it('converts XyzColor to css string', function (): void {
            $xyz = new XyzColor(x: 0.3, y: 0.2, z: 0.5);
            $result = $this->serializer->toCss($xyz);

            expect($result)->toBe('color(xyz-d65 0.3 0.2 0.5)');
        });

        it('converts unknown color to css string', function (): void {
            $unknown = new class () implements ColorValueInterface {
                public function getSpace(): string
                {
                    return 'custom';
                }

                public function getChannels(): array
                {
                    return [0.1, 0.2, 0.3];
                }

                public function getAlpha(): float
                {
                    return 1.0;
                }
            };

            $result = $this->serializer->toCss($unknown);

            expect($result)->toBe('color(custom 0.1 0.2 0.3)');
        });

        it('converts unknown color with alpha to css string', function (): void {
            $unknown = new class () implements ColorValueInterface {
                public function getSpace(): string
                {
                    return 'custom';
                }

                public function getChannels(): array
                {
                    return [0.1, 0.2, 0.3];
                }

                public function getAlpha(): float
                {
                    return 0.5;
                }
            };

            $result = $this->serializer->toCss($unknown);

            expect($result)->toBe('color(custom 0.1 0.2 0.3 / 0.50)');
        });
    });

    describe('toHex', function (): void {
        it('converts RgbColor to hex string', function (): void {
            $rgb = new RgbColor(r: 1.0, g: 0.0, b: 0.0, a: 1.0);
            $result = $this->serializer->toHex($rgb);

            expect($result)->toBe('#ff0000');
        });

        it('converts RgbColor with alpha to hex string', function (): void {
            $rgb = new RgbColor(r: 1.0, g: 0.0, b: 0.0, a: 0.5);
            $result = $this->serializer->toHex($rgb);

            expect($result)->toBe('#ff000080');
        });

        it('converts RgbColor with null channels to hex', function (): void {
            $rgb = new RgbColor(r: null, g: 0.5, b: 0.0, a: 1.0);
            $result = $this->serializer->toHex($rgb);

            expect($result)->toBe('#008000');
        });
    });

    describe('toCss for specific color types', function (): void {
        it('formats RgbColor without alpha', function (): void {
            $rgb = new RgbColor(r: 0.5, g: 0.5, b: 0.5, a: 1.0);
            $result = $this->serializer->toCss($rgb);

            expect($result)->toBe('rgb(128 128 128)');
        });

        it('formats RgbColor with alpha', function (): void {
            $rgb = new RgbColor(r: 0.5, g: 0.5, b: 0.5, a: 0.75);
            $result = $this->serializer->toCss($rgb);

            expect($result)->toBe('rgb(128 128 128 / 0.75)');
        });

        it('formats RgbColor with null channels', function (): void {
            $rgb = new RgbColor(r: null, g: null, b: null, a: 1.0);
            $result = $this->serializer->toCss($rgb);

            expect($result)->toBe('rgb(0 0 0)');
        });

        it('formats HslColor without alpha', function (): void {
            $hsl = new HslColor(h: 120.0, s: 100.0, l: 50.0, a: 1.0);
            $result = $this->serializer->toCss($hsl);

            expect($result)->toBe('hsl(120 100% 50%)');
        });

        it('formats HslColor with alpha', function (): void {
            $hsl = new HslColor(h: 120.0, s: 100.0, l: 50.0, a: 0.5);
            $result = $this->serializer->toCss($hsl);

            expect($result)->toBe('hsl(120 100% 50% / 0.50)');
        });

        it('formats HwbColor without alpha', function (): void {
            $hwb = new HwbColor(h: 240.0, w: 10.0, b: 20.0, a: 1.0);
            $result = $this->serializer->toCss($hwb);

            expect($result)->toBe('hwb(240 10% 20%)');
        });

        it('formats HwbColor with alpha', function (): void {
            $hwb = new HwbColor(h: 240.0, w: 10.0, b: 20.0, a: 0.6);
            $result = $this->serializer->toCss($hwb);

            expect($result)->toBe('hwb(240 10% 20% / 0.60)');
        });

        it('formats LabColor without alpha', function (): void {
            $lab = new LabColor(l: 100.0, a: 0.0, b: 0.0, alpha: 1.0);
            $result = $this->serializer->toCss($lab);

            expect($result)->toBe('lab(100% 0 0)');
        });

        it('formats LabColor with alpha', function (): void {
            $lab = new LabColor(l: 100.0, a: 0.0, b: 0.0, alpha: 0.8);
            $result = $this->serializer->toCss($lab);

            expect($result)->toBe('lab(100% 0 0 / 0.80)');
        });

        it('formats LchColor without alpha', function (): void {
            $lch = new LchColor(l: 50.0, c: 30.0, h: 180.0, alpha: 1.0);
            $result = $this->serializer->toCss($lch);

            expect($result)->toBe('lch(50% 30 180)');
        });

        it('formats LchColor with alpha', function (): void {
            $lch = new LchColor(l: 50.0, c: 30.0, h: 180.0, alpha: 0.9);
            $result = $this->serializer->toCss($lch);

            expect($result)->toBe('lch(50% 30 180 / 0.90)');
        });

        it('formats OklabColor without alpha', function (): void {
            $oklab = new OklabColor(l: 1.0, a: 0.0, b: 0.0, alpha: 1.0);
            $result = $this->serializer->toCss($oklab);

            expect($result)->toBe('oklab(1 0 0)');
        });

        it('formats OklabColor with alpha', function (): void {
            $oklab = new OklabColor(l: 1.0, a: 0.0, b: 0.0, alpha: 0.4);
            $result = $this->serializer->toCss($oklab);

            expect($result)->toBe('oklab(1 0 0 / 0.40)');
        });

        it('formats OklchColor without alpha', function (): void {
            $oklch = new OklchColor(l: 100.0, c: 0.0, h: 0.0, a: 1.0);
            $result = $this->serializer->toCss($oklch);

            expect($result)->toBe('oklch(100 0 0)');
        });

        it('formats OklchColor with alpha', function (): void {
            $oklch = new OklchColor(l: 100.0, c: 0.0, h: 0.0, a: 0.3);
            $result = $this->serializer->toCss($oklch);

            expect($result)->toBe('oklch(100 0 0 / 0.30)');
        });

        it('formats XyzColor', function (): void {
            $xyz = new XyzColor(x: 0.9505, y: 1.0, z: 1.0890);
            $result = $this->serializer->toCss($xyz);

            expect($result)->toBe('color(xyz-d65 0.9505 1 1.089)');
        });

        it('formats XyzColor with alpha', function (): void {
            $xyz = new XyzColor(x: 0.3, y: 0.2, z: 0.5, alpha: 0.5);
            $result = $this->serializer->toCss($xyz);

            expect($result)->toBe('color(xyz-d65 0.3 0.2 0.5 / 0.50)');
        });
    });

    describe('toHex edge cases', function (): void {
        it('clamps negative values to 0', function (): void {
            $rgb = new RgbColor(r: -0.5, g: 0.0, b: 0.0, a: 1.0);
            $result = $this->serializer->toHex($rgb);

            expect($result)->toBe('#000000');
        });

        it('clamps values above 1 to 255', function (): void {
            $rgb = new RgbColor(r: 1.5, g: 0.0, b: 0.0, a: 1.0);
            $result = $this->serializer->toHex($rgb);

            expect($result)->toBe('#ff0000');
        });
    });
});
