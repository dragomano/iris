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
        $red   = (int) round($this->colorSpaceConverter->clamp($rgb->r ?? 0.0, 255.0));
        $green = (int) round($this->colorSpaceConverter->clamp($rgb->g ?? 0.0, 255.0));
        $blue  = (int) round($this->colorSpaceConverter->clamp($rgb->b ?? 0.0, 255.0));
        $alpha = $this->colorSpaceConverter->clamp($rgb->a, 1.0);

        if (abs($alpha - 1.0) < 0.00001) {
            $namedColor = $this->findNamedColor($red, $green, $blue, $alpha);

            if ($namedColor !== null) {
                return $namedColor;
            }

            return sprintf('#%02x%02x%02x', $red, $green, $blue);
        }

        $alphaByte = (int) round($alpha * 255.0);

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
}
