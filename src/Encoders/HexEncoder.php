<?php

declare(strict_types=1);

namespace Bugo\Iris\Encoders;

use function dechex;
use function str_pad;
use function strtolower;

use const STR_PAD_LEFT;

final class HexEncoder
{
    public function encodeRgb(int $red, int $green, int $blue): string
    {
        return '#'
            . $this->toHexByte($red)
            . $this->toHexByte($green)
            . $this->toHexByte($blue);
    }

    public function encodeRgba(int $red, int $green, int $blue, int $alpha): string
    {
        return '#'
            . $this->toHexByte($red)
            . $this->toHexByte($green)
            . $this->toHexByte($blue)
            . $this->toHexByte($alpha);
    }

    public function toHexByte(int $value): string
    {
        return str_pad(strtolower(dechex($value)), 2, '0', STR_PAD_LEFT);
    }
}
