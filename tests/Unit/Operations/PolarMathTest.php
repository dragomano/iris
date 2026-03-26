<?php

declare(strict_types=1);

use Bugo\Iris\Operations\PolarMath;

describe('PolarMath', function (): void {
    beforeEach(function (): void {
        $this->math = new PolarMath();
    });

    describe('toRadians()', function (): void {
        it('converts 0 degrees to 0 radians', function (): void {
            expect($this->math->toRadians(0.0))->toBeCloseTo(0.0, 0.000001);
        });

        it('converts 180 degrees to pi radians', function (): void {
            expect($this->math->toRadians(180.0))->toBeCloseTo(M_PI, 0.000001);
        });

        it('converts 90 degrees to pi/2 radians', function (): void {
            expect($this->math->toRadians(90.0))->toBeCloseTo(M_PI / 2.0, 0.000001);
        });

        it('converts 360 degrees to 2*pi radians', function (): void {
            expect($this->math->toRadians(360.0))->toBeCloseTo(2.0 * M_PI, 0.000001);
        });

        it('converts negative degrees correctly', function (): void {
            expect($this->math->toRadians(-90.0))->toBeCloseTo(-M_PI / 2.0, 0.000001);
        });
    });

    describe('toCartesian()', function (): void {
        it('converts 0 hue (east) to correct cartesian coordinates', function (): void {
            [$a, $b] = $this->math->toCartesian(1.0, 0.0);

            expect($a)->toBeCloseTo(1.0, 0.000001)
                ->and($b)->toBeCloseTo(0.0, 0.000001);
        });

        it('converts 90 hue (north) to correct cartesian coordinates', function (): void {
            [$a, $b] = $this->math->toCartesian(1.0, 90.0);

            expect($a)->toBeCloseTo(0.0, 0.00001)
                ->and($b)->toBeCloseTo(1.0, 0.000001);
        });

        it('converts 180 hue (west) to correct cartesian coordinates', function (): void {
            [$a, $b] = $this->math->toCartesian(1.0, 180.0);

            expect($a)->toBeCloseTo(-1.0, 0.000001)
                ->and($b)->toBeCloseTo(0.0, 0.00001);
        });

        it('scales output by chroma', function (): void {
            [$a1, $b1] = $this->math->toCartesian(0.5, 45.0);
            [$a2, $b2] = $this->math->toCartesian(1.0, 45.0);

            expect($a1)->toBeCloseTo($a2 * 0.5, 0.000001)
                ->and($b1)->toBeCloseTo($b2 * 0.5, 0.000001);
        });

        it('returns zero vector for zero chroma', function (): void {
            [$a, $b] = $this->math->toCartesian(0.0, 45.0);

            expect($a)->toBeCloseTo(0.0, 0.000001)
                ->and($b)->toBeCloseTo(0.0, 0.000001);
        });
    });
});
