<?php

declare(strict_types=1);

namespace Bugo\Iris\Operations;

use function cos;
use function sin;

use const M_PI;

final readonly class PolarMath
{
    /**
     * @return array{0: float, 1: float}
     */
    public function toCartesian(float $chroma, float $hue): array
    {
        $radians = $this->toRadians($hue);

        return [
            $chroma * cos($radians),
            $chroma * sin($radians),
        ];
    }

    public function toRadians(float $degrees): float
    {
        return $degrees * M_PI / 180.0;
    }
}
