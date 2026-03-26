<?php

declare(strict_types=1);

namespace Bugo\Iris;

use Bugo\Iris\Spaces\RgbColor;

use function array_key_exists;
use function hexdec;
use function strlen;
use function strtolower;

final readonly class LiteralParser
{
    public function toRgb(string $value, ?RgbColor $currentColorContext = null): ?RgbColor
    {
        $normalized = strtolower($value);

        if ($normalized === 'currentcolor') {
            return $currentColorContext;
        }

        if (array_key_exists($normalized, NamedColors::NAMED_RGB)) {
            $named = NamedColors::NAMED_RGB[$normalized];

            return new RgbColor(
                r: $named[0],
                g: $named[1],
                b: $named[2],
                a: $named[3] ?? 1.0
            );
        }

        return $this->hexToRgb($normalized);
    }

    private function hexToRgb(string $value): ?RgbColor
    {
        $valueLength = strlen($value);

        if ($valueLength < 4 || $value[0] !== '#') {
            return null;
        }

        $hex = $value;

        if ($valueLength === 4 || $valueLength === 5) {
            $expanded = '#';

            for ($i = 1; $i < $valueLength; $i++) {
                $expanded .= $hex[$i] . $hex[$i];
            }

            $hex = $expanded;
        }

        $hexLength = strlen($hex);

        if ($hexLength === 7) {
            return new RgbColor(
                r: (float) hexdec($hex[1] . $hex[2]),
                g: (float) hexdec($hex[3] . $hex[4]),
                b: (float) hexdec($hex[5] . $hex[6])
            );
        }

        if ($hexLength === 9) {
            return new RgbColor(
                r: (float) hexdec($hex[1] . $hex[2]),
                g: (float) hexdec($hex[3] . $hex[4]),
                b: (float) hexdec($hex[5] . $hex[6]),
                a: (float) hexdec($hex[7] . $hex[8]) / 255.0
            );
        }

        return null;
    }
}
