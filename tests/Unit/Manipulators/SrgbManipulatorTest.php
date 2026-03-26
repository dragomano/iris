<?php

declare(strict_types=1);

use Bugo\Iris\Manipulators\SrgbManipulator;

describe('SrgbManipulator', function (): void {
    beforeEach(function (): void {
        $this->manipulator = new SrgbManipulator();
    });

    describe('adjust', function (): void {
        it('adjust increases red channel', function (): void {
            [$r, $g, $b] = $this->manipulator->adjust(0.5, 0.3, 0.1, ['red' => 0.1]);

            expect(round($r, 4))->toBe(0.6)
                ->and(round($g, 4))->toBe(0.3)
                ->and(round($b, 4))->toBe(0.1);
        });

        it('adjust decreases green channel', function (): void {
            [$r, $g, $b] = $this->manipulator->adjust(0.5, 0.3, 0.1, ['green' => -0.1]);

            expect(round($r, 4))->toBe(0.5)
                ->and(round($g, 4))->toBe(0.2)
                ->and(round($b, 4))->toBe(0.1);
        });

        it('adjust clamps to 1.0 maximum', function (): void {
            [$r, ,] = $this->manipulator->adjust(0.9, 0.3, 0.1, ['red' => 0.5]);

            expect($r)->toBe(1.0);
        });

        it('adjust clamps to 0.0 minimum', function (): void {
            [, , $b] = $this->manipulator->adjust(0.5, 0.3, 0.1, ['blue' => -0.5]);

            expect($b)->toBe(0.0);
        });

        it('adjust without changes returns original values', function (): void {
            [$r, $g, $b] = $this->manipulator->adjust(0.5, 0.3, 0.1, []);

            expect($r)->toBe(0.5)
                ->and($g)->toBe(0.3)
                ->and($b)->toBe(0.1);
        });
    });

    describe('change', function (): void {
        it('change sets red to new value ignoring current', function (): void {
            [$r, $g, $b] = $this->manipulator->change(0.5, 0.3, 0.1, ['red' => 0.8]);

            expect($r)->toBe(0.8)
                ->and(round($g, 4))->toBe(0.3)
                ->and(round($b, 4))->toBe(0.1);
        });

        it('change sets blue to new value', function (): void {
            [$r, , $b] = $this->manipulator->change(0.5, 0.3, 0.1, ['blue' => 0.9]);

            expect(round($r, 4))->toBe(0.5)
                ->and(round($b, 4))->toBe(0.9);
        });

        it('change clamps value to 1.0', function (): void {
            [, $g,] = $this->manipulator->change(0.5, 0.3, 0.1, ['green' => 1.5]);

            expect($g)->toBe(1.0);
        });

        it('change clamps value to 0.0', function (): void {
            [$r, ,] = $this->manipulator->change(0.5, 0.3, 0.1, ['red' => -0.3]);

            expect($r)->toBe(0.0);
        });
    });
});
