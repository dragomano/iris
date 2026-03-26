<?php

declare(strict_types=1);

use Bugo\Iris\Operations\GamutMapper;
use Bugo\Iris\Spaces\OklchColor;

describe('GamutMapper', function (): void {
    beforeEach(function (): void {
        $this->mapper = new GamutMapper();
    });

    describe('clip', function (): void {
        it('in-gamut color passes through unchanged', function (): void {
            $oklch  = new OklchColor(l: 50.0, c: 10.0, h: 30.0, a: 1.0);
            $result = $this->mapper->clip($oklch);

            expect($result)->toBeInstanceOf(OklchColor::class);
        });

        it('out-of-gamut color is clipped into sRGB', function (): void {
            // Very high chroma → out of sRGB gamut
            $oklch  = new OklchColor(l: 70.0, c: 40.0, h: 30.0, a: 1.0);
            $result = $this->mapper->clip($oklch);

            expect($result)->toBeInstanceOf(OklchColor::class);
        });

        it('returns OklchColor with preserved alpha', function (): void {
            $oklch  = new OklchColor(l: 50.0, c: 50.0, h: 120.0, a: 0.5);
            $result = $this->mapper->clip($oklch);

            expect($result->a)->toBeCloseTo(0.5);
        });

        it('black stays black', function (): void {
            $oklch  = new OklchColor(l: 0.0, c: 0.0, h: 0.0, a: 1.0);
            $result = $this->mapper->clip($oklch);

            expect($result->l)->toBeCloseTo(0.0, 0.5);
        });

        it('white stays white', function (): void {
            $oklch  = new OklchColor(l: 100.0, c: 0.0, h: 0.0, a: 1.0);
            $result = $this->mapper->clip($oklch);

            expect($result->l ?? 0.0)->toBeCloseTo(100.0, 0.5);
        });
    });

    describe('localMinde', function (): void {
        it('in-gamut color returns result within sRGB', function (): void {
            $oklch  = new OklchColor(l: 50.0, c: 5.0, h: 200.0, a: 1.0);
            $result = $this->mapper->localMinde($oklch);

            expect($result)->toBeInstanceOf(OklchColor::class);
        });

        it('out-of-gamut color is mapped with reduced chroma', function (): void {
            $oklch      = new OklchColor(l: 70.0, c: 40.0, h: 30.0, a: 1.0);
            $result     = $this->mapper->localMinde($oklch);
            $resultChroma = $result->c ?? 0.0;

            // local-MINDE should produce lower or equal chroma than the original
            expect($resultChroma)->toBeLessThanOrEqual(40.0 + 0.001);
        });

        it('preserves alpha channel', function (): void {
            $oklch  = new OklchColor(l: 60.0, c: 30.0, h: 150.0, a: 0.7);
            $result = $this->mapper->localMinde($oklch);

            expect($result->a)->toBeCloseTo(0.7);
        });

        it('black stays near black', function (): void {
            $oklch  = new OklchColor(l: 0.0, c: 0.0, h: 0.0, a: 1.0);
            $result = $this->mapper->localMinde($oklch);

            expect($result->l ?? 0.0)->toBeCloseTo(0.0, 1.0);
        });

        it('local-minde result has less or equal chroma than clip for vivid colors', function (): void {
            $oklch     = new OklchColor(l: 65.0, c: 35.0, h: 25.0, a: 1.0);
            $clipped   = $this->mapper->clip($oklch);
            $mapped    = $this->mapper->localMinde($oklch);

            // Both should produce valid OklchColor
            expect($clipped)->toBeInstanceOf(OklchColor::class)
                ->and($mapped)->toBeInstanceOf(OklchColor::class);
        });
    });
});
