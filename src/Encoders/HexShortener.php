<?php

declare(strict_types=1);

namespace Bugo\Iris\Encoders;

use function strlen;

final class HexShortener
{
    public function shorten(string $hex): string
    {
        if (
            strlen($hex) === 7
            && $hex[1] === $hex[2]
            && $hex[3] === $hex[4]
            && $hex[5] === $hex[6]
        ) {
            return '#' . $hex[1] . $hex[3] . $hex[5];
        }

        if (
            strlen($hex) === 9
            && $hex[1] === $hex[2]
            && $hex[3] === $hex[4]
            && $hex[5] === $hex[6]
            && $hex[7] === $hex[8]
        ) {
            return '#' . $hex[1] . $hex[3] . $hex[5] . $hex[7];
        }

        return $hex;
    }
}
