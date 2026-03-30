<?php

declare(strict_types=1);

namespace Bugo\Iris\Serializers;

use Bugo\Iris\Contracts\ColorValueInterface;
use Bugo\Iris\Encoders\HexEncoder;
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\HwbColor;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\LchColor;
use Bugo\Iris\Spaces\OklabColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;
use Bugo\Iris\Spaces\XyzColor;

use function implode;
use function max;
use function min;
use function round;
use function sprintf;

final readonly class CssSerializer
{
    public function __construct(private HexEncoder $hexEncoder = new HexEncoder()) {}

    public function toCss(ColorValueInterface $color, bool $useHex = false): string
    {
        return match ($color::class) {
            RgbColor::class   => $useHex ? $this->toHex($color) : $this->serializeRgb($color),
            HslColor::class   => $this->serializeHsl($color),
            HwbColor::class   => $this->serializeHwb($color),
            LabColor::class   => $this->serializeLab($color),
            LchColor::class   => $this->serializeLch($color),
            OklabColor::class => $this->serializeOklab($color),
            OklchColor::class => $this->serializeOklch($color),
            XyzColor::class   => $this->serializeXyz($color),
            default           => $this->serializeUnknown($color),
        };
    }

    public function toHex(RgbColor $color): string
    {
        $r = $this->toByte($color->r ?? 0.0);
        $g = $this->toByte($color->g ?? 0.0);
        $b = $this->toByte($color->b ?? 0.0);
        $a = $this->toByte($color->a);

        return $a < 255
            ? $this->hexEncoder->encodeRgba($r, $g, $b, $a)
            : $this->hexEncoder->encodeRgb($r, $g, $b);
    }

    private function serializeRgb(RgbColor $color): string
    {
        $r = $this->toByte($color->r ?? 0.0);
        $g = $this->toByte($color->g ?? 0.0);
        $b = $this->toByte($color->b ?? 0.0);

        if ($color->a < 1.0) {
            return sprintf('rgb(%d %d %d / %.2f)', $r, $g, $b, $color->a);
        }

        return sprintf('rgb(%d %d %d)', $r, $g, $b);
    }

    private function serializeHsl(HslColor $color): string
    {
        $h = $color->h ?? 0.0;
        $s = $color->s ?? 0.0;
        $l = $color->l ?? 0.0;

        if ($color->a < 1.0) {
            return sprintf('hsl(%s %s%% %s%% / %.2f)', $h, $s, $l, $color->a);
        }

        return sprintf('hsl(%s %s%% %s%%)', $h, $s, $l);
    }

    private function serializeHwb(HwbColor $color): string
    {
        $h = $color->h ?? 0.0;
        $w = $color->w ?? 0.0;
        $b = $color->b ?? 0.0;

        if ($color->a < 1.0) {
            return sprintf('hwb(%s %s%% %s%% / %.2f)', $h, $w, $b, $color->a);
        }

        return sprintf('hwb(%s %s%% %s%%)', $h, $w, $b);
    }

    private function serializeLab(LabColor $color): string
    {
        $l = $color->l ?? 0.0;
        $a = $color->a ?? 0.0;
        $b = $color->b ?? 0.0;

        if ($color->alpha < 1.0) {
            return sprintf('lab(%s%% %s %s / %.2f)', $l, $a, $b, $color->alpha);
        }

        return sprintf('lab(%s%% %s %s)', $l, $a, $b);
    }

    private function serializeLch(LchColor $color): string
    {
        $l = $color->l ?? 0.0;
        $c = $color->c ?? 0.0;
        $h = $color->h ?? 0.0;

        if ($color->alpha < 1.0) {
            return sprintf('lch(%s%% %s %s / %.2f)', $l, $c, $h, $color->alpha);
        }

        return sprintf('lch(%s%% %s %s)', $l, $c, $h);
    }

    private function serializeOklab(OklabColor $color): string
    {
        $l = $color->l ?? 0.0;
        $a = $color->a ?? 0.0;
        $b = $color->b ?? 0.0;

        if ($color->alpha < 1.0) {
            return sprintf('oklab(%s %s %s / %.2f)', $l, $a, $b, $color->alpha);
        }

        return sprintf('oklab(%s %s %s)', $l, $a, $b);
    }

    private function serializeOklch(OklchColor $color): string
    {
        $l = $color->l ?? 0.0;
        $c = $color->c ?? 0.0;
        $h = $color->h ?? 0.0;

        if ($color->a < 1.0) {
            return sprintf('oklch(%s %s %s / %.2f)', $l, $c, $h, $color->a);
        }

        return sprintf('oklch(%s %s %s)', $l, $c, $h);
    }

    private function serializeXyz(XyzColor $color): string
    {
        $x = $color->x;
        $y = $color->y;
        $z = $color->z;

        return sprintf('color(xyz-d65 %s %s %s)', $x, $y, $z);
    }

    private function serializeUnknown(ColorValueInterface $color): string
    {
        $channels = $color->getChannels();
        $space    = $color->getSpace();
        $alpha    = $color->getAlpha();

        $channelStrs = [];
        foreach ($channels as $channel) {
            $channelStrs[] = $channel === null ? 'none' : (string) $channel;
        }

        $channelList = implode(' ', $channelStrs);

        return $alpha < 1.0
            ? sprintf('color(%s %s / %.2f)', $space, $channelList, $alpha)
            : sprintf('color(%s %s)', $space, $channelList);
    }

    private function toByte(float $value): int
    {
        return max(0, min(255, (int) round($value * 255.0)));
    }
}
