<?php

declare(strict_types=1);

use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Manipulators\PerceptualManipulator;
use Bugo\Iris\Serializers\CssSerializer;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\RgbColor;

describe('perceptual pipeline', function (): void {
    beforeEach(function (): void {
        $this->converter   = new SpaceConverter();
        $this->manipulator = new PerceptualManipulator();
        $this->serializer  = new CssSerializer();
    });

    it('converts rgb to oklch, adjusts it and serializes to CSS', function (): void {
        $rgb = new RgbColor(r: 255.0, g: 128.0, b: 0.0, a: 1.0);
        $oklch = $this->converter->rgbToOklch($rgb);
        $adjusted = $this->manipulator->adjustOklch($oklch, [
            'lightness' => 5.0,
            'chroma'    => -0.02,
            'hue'       => 15.0,
            'alpha'     => -0.25,
        ]);

        expect($this->serializer->toCss($adjusted))
            ->toBe('oklch(78.189484719883 0.16580319653171 67.984673920234 / 0.75)');
    });

    it('changes lab values and keeps them serializable as CSS', function (): void {
        $lab = new LabColor(l: 50.0, a: 20.0, b: -30.0, alpha: 1.0);
        $changed = $this->manipulator->changeLab($lab, [
            'lightness' => 70.0,
            'a'         => 10.0,
            'b'         => -5.0,
            'alpha'     => 0.5,
        ]);

        expect($this->serializer->toCss($changed))->toBe('lab(70% 10 -5 / 0.50)');
    });
});
