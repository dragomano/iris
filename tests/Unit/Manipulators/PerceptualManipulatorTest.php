<?php

declare(strict_types=1);

use Bugo\Iris\Manipulators\PerceptualManipulator;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\OklchColor;

describe('PerceptualManipulator', function (): void {
    beforeEach(function (): void {
        $this->manipulator = new PerceptualManipulator();
    });

    describe('adjustOklch', function (): void {
        it('adjustOklch increases lightness', function (): void {
            $color  = new OklchColor(50.0, 0.15, 120.0, 1.0);
            $result = $this->manipulator->adjustOklch($color, ['lightness' => 10.0]);

            expect($result->l)->toBe(60.0)
                ->and($result->c)->toBe(0.15)
                ->and($result->h)->toBe(120.0);
        });

        it('adjustOklch increases chroma', function (): void {
            $color  = new OklchColor(50.0, 0.15, 120.0, 1.0);
            $result = $this->manipulator->adjustOklch($color, ['chroma' => 0.05]);

            expect(round($result->c, 4))->toBe(0.2);
        });

        it('adjustOklch adjusts hue with normalization', function (): void {
            $color  = new OklchColor(50.0, 0.15, 350.0, 1.0);
            $result = $this->manipulator->adjustOklch($color, ['hue' => 20.0]);

            // 350 + 20 = 370, normalized to 10
            expect(round($result->h, 4))->toBe(10.0);
        });

        it('adjustOklch clamps lightness to 100', function (): void {
            $color  = new OklchColor(90.0, 0.15, 120.0, 1.0);
            $result = $this->manipulator->adjustOklch($color, ['lightness' => 20.0]);

            expect($result->l)->toBe(100.0);
        });

        it('adjustOklch clamps lightness to 0', function (): void {
            $color  = new OklchColor(5.0, 0.15, 120.0, 1.0);
            $result = $this->manipulator->adjustOklch($color, ['lightness' => -10.0]);

            expect($result->l)->toBe(0.0);
        });

        it('adjustOklch clamps chroma to 0 (non-negative)', function (): void {
            $color  = new OklchColor(50.0, 0.05, 120.0, 1.0);
            $result = $this->manipulator->adjustOklch($color, ['chroma' => -0.10]);

            expect($result->c)->toBe(0.0);
        });

        it('adjustOklch clamps alpha to 1.0', function (): void {
            $color  = new OklchColor(50.0, 0.15, 120.0, 0.5);
            $result = $this->manipulator->adjustOklch($color, ['alpha' => 0.8]);

            expect($result->a)->toBe(1.0);
        });

        it('adjustOklch without changes returns original values', function (): void {
            $color  = new OklchColor(60.0, 0.2, 180.0, 0.8);
            $result = $this->manipulator->adjustOklch($color, []);

            expect($result->l)->toBe(60.0)
                ->and($result->c)->toBe(0.2)
                ->and($result->h)->toBe(180.0)
                ->and($result->a)->toBe(0.8);
        });
    });

    describe('changeOklch', function (): void {
        it('changeOklch sets lightness to exact value', function (): void {
            $color  = new OklchColor(50.0, 0.15, 120.0, 1.0);
            $result = $this->manipulator->changeOklch($color, ['lightness' => 80.0]);

            expect($result->l)->toBe(80.0)
                ->and($result->c)->toBe(0.15)
                ->and($result->h)->toBe(120.0);
        });

        it('changeOklch sets chroma to exact value', function (): void {
            $color  = new OklchColor(50.0, 0.15, 120.0, 1.0);
            $result = $this->manipulator->changeOklch($color, ['chroma' => 0.30]);

            expect(round($result->c, 4))->toBe(0.3);
        });

        it('changeOklch sets hue to exact value', function (): void {
            $color  = new OklchColor(50.0, 0.15, 120.0, 1.0);
            $result = $this->manipulator->changeOklch($color, ['hue' => 240.0]);

            expect($result->h)->toBe(240.0);
        });
    });

    describe('adjustLab', function (): void {
        it('adjustLab increases lightness', function (): void {
            $color  = new LabColor(50.0, 20.0, -10.0, 1.0);
            $result = $this->manipulator->adjustLab($color, ['lightness' => 10.0]);

            expect($result->l)->toBe(60.0);
        });

        it('adjustLab adjusts a channel without bounds', function (): void {
            $color  = new LabColor(50.0, 20.0, -10.0, 1.0);
            $result = $this->manipulator->adjustLab($color, ['a' => -30.0]);

            expect(round($result->a, 4))->toBe(-10.0);
        });
    });

    describe('changeLab', function (): void {
        it('changeLab sets lightness to exact value', function (): void {
            $color  = new LabColor(50.0, 20.0, -10.0, 1.0);
            $result = $this->manipulator->changeLab($color, ['lightness' => 70.0]);

            expect($result->l)->toBe(70.0);
        });

        it('changeLab sets b channel', function (): void {
            $color  = new LabColor(50.0, 20.0, -10.0, 1.0);
            $result = $this->manipulator->changeLab($color, ['b' => 30.0]);

            expect(round($result->b, 4))->toBe(30.0);
        });

        it('changeLab sets a channel to exact value', function (): void {
            $color  = new LabColor(50.0, 20.0, -10.0, 1.0);
            $result = $this->manipulator->changeLab($color, ['a' => -50.0]);

            expect($result->a)->toBe(-50.0);
        });
    });
});
