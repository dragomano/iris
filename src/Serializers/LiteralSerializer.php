<?php

declare(strict_types=1);

namespace Bugo\Iris\Serializers;

use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\NamedColors;
use Bugo\Iris\Spaces\RgbColor;

use function abs;
use function round;
use function sprintf;

final readonly class LiteralSerializer
{
    public function __construct(private SpaceConverter $colorSpaceConverter = new SpaceConverter()) {}

    public function serialize(RgbColor $rgb): string
    {
        $red   = $this->toColorByte($rgb->r ?? 0.0);
        $green = $this->toColorByte($rgb->g ?? 0.0);
        $blue  = $this->toColorByte($rgb->b ?? 0.0);
        $alpha = $this->colorSpaceConverter->clamp($rgb->a, 1.0);

        if (abs($alpha - 1.0) >= 0.00001) {
            return sprintf(
                '#%02x%02x%02x%02x',
                $red,
                $green,
                $blue,
                $this->toColorByte($alpha * 255.0)
            );
        }

        $namedColor = $this->findNamedColor($red, $green, $blue, $alpha);

        return $namedColor ?? sprintf('#%02x%02x%02x', $red, $green, $blue);
    }

    public function findNamedColor(int $red, int $green, int $blue, float $alpha): ?string
    {
        if (abs(1.0 - $alpha) < 0.00001) {
            foreach (['black', 'white'] as $name) {
                $channels = NamedColors::NAMED_RGB[$name];

                if (
                    (int) $channels[0] === $red
                    && (int) $channels[1] === $green
                    && (int) $channels[2] === $blue
                ) {
                    return $name;
                }
            }
        }

        return null;
    }

    private function toColorByte(float $value): int
    {
        return (int) round($this->colorSpaceConverter->clamp($value, 255.0));
    }
}
