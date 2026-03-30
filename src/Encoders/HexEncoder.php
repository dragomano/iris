<?php

declare(strict_types=1);

namespace Bugo\Iris\Encoders;

use function dechex;
use function sprintf;
use function strlen;

final class HexEncoder
{
    public function encodeRgb(int $red, int $green, int $blue): string
    {
        return sprintf(
            '#%s%s%s',
            $this->toHexByte($red),
            $this->toHexByte($green),
            $this->toHexByte($blue)
        );
    }

    public function encodeRgba(int $red, int $green, int $blue, int $alpha): string
    {
        return sprintf(
            '#%s%s%s%s',
            $this->toHexByte($red),
            $this->toHexByte($green),
            $this->toHexByte($blue),
            $this->toHexByte($alpha)
        );
    }

    public function toHexByte(int $value): string
    {
        $hex = dechex($value);

        if (strlen($hex) === 1) {
            return '0' . $hex;
        }

        return $hex;
    }
}
