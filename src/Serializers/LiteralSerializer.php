<?php

declare(strict_types=1);

namespace Bugo\Iris\Serializers;

use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\NamedColors;
use Bugo\Iris\Spaces\RgbColor;

use function abs;
use function floor;
use function round;
use function sprintf;

final readonly class LiteralSerializer
{
    private const HALF_STEP_EPSILON = 1e-12;

    public function __construct(private SpaceConverter $colorSpaceConverter = new SpaceConverter()) {}

    public function serialize(RgbColor $rgb): string
    {
        $red   = $this->toColorByte($rgb->r ?? 0.0);
        $green = $this->toColorByte($rgb->g ?? 0.0);
        $blue  = $this->toColorByte($rgb->b ?? 0.0);
        $alpha = $this->colorSpaceConverter->clamp($rgb->a, 1.0);

        if (abs($alpha - 1.0) < 0.00001) {
            $namedColor = $this->findNamedColor($red, $green, $blue, $alpha);

            if ($namedColor !== null) {
                return $namedColor;
            }

            return sprintf('#%02x%02x%02x', $red, $green, $blue);
        }

        $alphaByte = $this->toColorByte($alpha * 255.0);

        return sprintf('#%02x%02x%02x%02x', $red, $green, $blue, $alphaByte);
    }

    public function findNamedColor(int $red, int $green, int $blue, float $alpha): ?string
    {
        foreach (['black', 'white'] as $name) {
            $channels   = NamedColors::NAMED_RGB[$name];
            $namedAlpha = 1.0;

            if (
                (int) $channels[0] === $red
                && (int) $channels[1] === $green
                && (int) $channels[2] === $blue
                && abs($namedAlpha - $alpha) < 0.00001
            ) {
                return $name;
            }
        }

        return null;
    }

    private function toColorByte(float $value): int
    {
        $value    = $this->colorSpaceConverter->clamp($value, 255.0);
        $fraction = $value - floor($value);

        if ($fraction < 0.5 && abs($fraction - 0.5) < self::HALF_STEP_EPSILON) {
            return (int) floor($value);
        }

        return (int) round($value);
    }
}
