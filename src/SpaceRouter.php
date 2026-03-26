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
        private SpaceConverter $colorSpaceConverter = new SpaceConverter()
    ) {}

    public function convertToRgba(string $space, float $c1, float $c2, float $c3, float $opacity): RgbColor
    {
        return match ($space) {
            'srgb'              => $this->colorSpaceConverter->srgbChannelsToSrgba($c1, $c2, $c3, $opacity),
            'srgb-linear'       => $this->colorSpaceConverter->linearSrgbChannelsToSrgba($c1, $c2, $c3, $opacity),
            'display-p3'        => $this->colorSpaceConverter->displayP3ChannelsToSrgba($c1, $c2, $c3, $opacity),
            'display-p3-linear' => $this->colorSpaceConverter->linearDisplayP3ChannelsToSrgba($c1, $c2, $c3, $opacity),
            'a98-rgb'           => $this->colorSpaceConverter->a98RgbChannelsToSrgba($c1, $c2, $c3, $opacity),
            'prophoto-rgb'      => $this->colorSpaceConverter->prophotoRgbChannelsToSrgba($c1, $c2, $c3, $opacity),
            'rec2020'           => $this->colorSpaceConverter->rec2020ChannelsToSrgba($c1, $c2, $c3, $opacity),
            'xyz', 'xyz-d65'    => $this->colorSpaceConverter->xyzD65ToSrgba(new XyzColor($c1, $c2, $c3), $opacity),
            'xyz-d50'           => $this->colorSpaceConverter->xyzD50ToSrgba(new XyzColor($c1, $c2, $c3), $opacity),
            'lab'               => $this->colorSpaceConverter->labChannelsToSrgba($c1, $c2, $c3, $opacity),
            'lch'               => $this->colorSpaceConverter->lchChannelsToSrgba($c1, $c2, $c3, $opacity),
            'oklab'             => $this->colorSpaceConverter->oklabChannelsToSrgba($c1, $c2, $c3, $opacity),
            'oklch'             => $this->colorSpaceConverter->oklchChannelsToSrgba($c1, $c2, $c3, $opacity),
            default             => throw new UnsupportedColorSpace($space),
        };
    }

    public function convertToXyzD65(string $space, float $c1, float $c2, float $c3): XyzColor
    {
        return match ($space) {
            'srgb'              => $this->colorSpaceConverter->srgbChannelsToXyzD65($c1, $c2, $c3),
            'srgb-linear'       => $this->colorSpaceConverter->linearSrgbChannelsToXyzD65($c1, $c2, $c3),
            'display-p3'        => $this->colorSpaceConverter->displayP3ChannelsToXyzD65($c1, $c2, $c3),
            'display-p3-linear' => $this->colorSpaceConverter->linearDisplayP3ChannelsToXyzD65($c1, $c2, $c3),
            'a98-rgb'           => $this->colorSpaceConverter->a98RgbChannelsToXyzD65($c1, $c2, $c3),
            'prophoto-rgb'      => $this->colorSpaceConverter->prophotoRgbChannelsToXyzD65($c1, $c2, $c3),
            'rec2020'           => $this->colorSpaceConverter->rec2020ChannelsToXyzD65($c1, $c2, $c3),
            'xyz', 'xyz-d65'    => new XyzColor($c1, $c2, $c3),
            'xyz-d50'           => $this->colorSpaceConverter->xyzD50ToXyzD65(new XyzColor($c1, $c2, $c3)),
            'lab'               => $this->colorSpaceConverter->labChannelsToXyzD65($c1, $c2, $c3),
            'lch'               => $this->colorSpaceConverter->lchChannelsToXyzD65($c1, $c2, $c3),
            'oklab'             => $this->colorSpaceConverter->oklabChannelsToXyzD65($c1, $c2, $c3),
            'oklch'             => $this->colorSpaceConverter->oklchChannelsToXyzD65($c1, $c2, $c3),
            default             => throw new UnsupportedColorSpace($space),
        };
    }
}
