<?php

declare(strict_types=1);

namespace Bugo\Iris;

use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Exceptions\UnsupportedColorSpace;
use Bugo\Iris\Spaces\RgbColor;
use Bugo\Iris\Spaces\XyzColor;

final readonly class SpaceRouter
{
    public function __construct(
        private SpaceConverter $colorSpaceConverter = new SpaceConverter(),
    ) {}

    public function convertToRgba(string $space, float $c1, float $c2, float $c3, float $opacity): RgbColor
    {
        return match ($space) {
            'srgb'              => $this->colorSpaceConverter->srgbChannelsToRgb($c1, $c2, $c3, $opacity),
            'srgb-linear'       => $this->colorSpaceConverter->linSrgbChannelsToRgb($c1, $c2, $c3, $opacity),
            'display-p3'        => $this->colorSpaceConverter->p3ChannelsToRgb($c1, $c2, $c3, $opacity),
            'display-p3-linear' => $this->colorSpaceConverter->xyzD65ToRgb(
                $this->colorSpaceConverter->linP3ToXyzD65($c1, $c2, $c3),
                $opacity,
            ),
            'a98-rgb'           => $this->colorSpaceConverter->a98ChannelsToRgb($c1, $c2, $c3, $opacity),
            'prophoto-rgb'      => $this->colorSpaceConverter->prophotoChannelsToRgb($c1, $c2, $c3, $opacity),
            'rec2020'           => $this->colorSpaceConverter->rec2020ChannelsToRgb($c1, $c2, $c3, $opacity),
            'xyz', 'xyz-d65'    => $this->colorSpaceConverter->xyzD65ToRgb(new XyzColor($c1, $c2, $c3), $opacity),
            'xyz-d50'           => $this->colorSpaceConverter->xyzD50ToRgb(new XyzColor($c1, $c2, $c3), $opacity),
            'lab'               => $this->colorSpaceConverter->labChannelsToRgb($c1, $c2, $c3, $opacity),
            'lch'               => $this->colorSpaceConverter->lchChannelsToRgb($c1, $c2, $c3, $opacity),
            'oklab'             => $this->colorSpaceConverter->oklabChannelsToRgb($c1, $c2, $c3, $opacity),
            'oklch'             => $this->colorSpaceConverter->oklchChannelsToRgb($c1, $c2, $c3, $opacity),
            default             => throw new UnsupportedColorSpace($space),
        };
    }

    public function convertToXyzD65(string $space, float $c1, float $c2, float $c3): XyzColor
    {
        return match ($space) {
            'srgb'              => $this->colorSpaceConverter->srgbToXyzD65($c1, $c2, $c3),
            'srgb-linear'       => $this->colorSpaceConverter->linSrgbToXyzD65($c1, $c2, $c3),
            'display-p3'        => $this->colorSpaceConverter->p3ToXyzD65($c1, $c2, $c3),
            'display-p3-linear' => $this->colorSpaceConverter->linP3ToXyzD65($c1, $c2, $c3),
            'a98-rgb'           => $this->colorSpaceConverter->a98ToXyzD65($c1, $c2, $c3),
            'prophoto-rgb'      => $this->colorSpaceConverter->prophotoToXyzD65($c1, $c2, $c3),
            'rec2020'           => $this->colorSpaceConverter->rec2020ToXyzD65($c1, $c2, $c3),
            'xyz', 'xyz-d65'    => new XyzColor($c1, $c2, $c3),
            'xyz-d50'           => $this->colorSpaceConverter->xyzD50ToXyzD65(new XyzColor($c1, $c2, $c3)),
            'lab'               => $this->colorSpaceConverter->labToXyzD65($c1, $c2, $c3),
            'lch'               => $this->colorSpaceConverter->lchToXyzD65($c1, $c2, $c3),
            'oklab'             => $this->colorSpaceConverter->oklabToXyzD65($c1, $c2, $c3),
            'oklch'             => $this->colorSpaceConverter->oklchToXyzD65($c1, $c2, $c3),
            default             => throw new UnsupportedColorSpace($space),
        };
    }
}
