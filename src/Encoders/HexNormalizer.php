<?php

declare(strict_types=1);

namespace Bugo\Iris\Encoders;

use function ctype_xdigit;
use function in_array;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

final readonly class HexNormalizer
{
    public function __construct(
        private HexShortener $hexColorShortener = new HexShortener()
    ) {}

    public function normalize(string $value): ?string
    {
        if (! str_starts_with($value, '#')) {
            return null;
        }

        $hex = strtolower(substr($value, 1));
        $len = strlen($hex);

        if (! in_array($len, [3, 4, 6, 8], true) || ! ctype_xdigit($hex)) {
            return null;
        }

        return $this->hexColorShortener->shorten('#' . $hex);
    }
}
