<?php

declare(strict_types=1);

namespace Bugo\Iris\Serializers;

use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Encoders\HexEncoder;
use Bugo\Iris\Encoders\HexNormalizer;
use Bugo\Iris\LiteralParser;
use Bugo\Iris\NamedColors;
use Bugo\Iris\Operations\ColorMixResolver;
use Bugo\Iris\SpaceRouter;
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\LchColor;
use Bugo\Iris\Spaces\OklabColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;
use Closure;

use function abs;
use function ctype_space;
use function explode;
use function is_numeric;
use function round;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function trim;

use const M_PI;
use const PHP_ROUND_HALF_UP;

final readonly class Serializer
{
    public function __construct(
        private HexNormalizer $hexColorNormalizer = new HexNormalizer(),
        private HexEncoder $hexColorEncoder = new HexEncoder(),
        private SpaceConverter $colorSpaceConverter = new SpaceConverter(),
        private SpaceRouter $colorSpaceRouter = new SpaceRouter()
    ) {}

    public function serialize(string $value, bool $outputHexColors): string
    {
        $normalizedHex = $this->hexColorNormalizer->normalize($value);

        if ($normalizedHex !== null) {
            return $normalizedHex;
        }

        if (! $outputHexColors) {
            return $value;
        }

        $trimmed = trim($value);

        // CSS Level 4: 'none' in legacy comma syntax is a syntax error — preserve as-is
        if ($this->isLegacyColorWithNone($trimmed)) {
            return $value;
        }

        $rgb = $this->convertSupportedFunctionalColorToRgb($trimmed);

        // null only for unrecognized functions (default => null in match) → preserve original
        return $rgb !== null ? $this->encodeHex($rgb) : $value;
    }

    private function convertSupportedFunctionalColorToRgb(string $value): ?RgbColor
    {
        $black = new RgbColor(0, 0, 0, 1.0);

        /** @var array<string, Closure(string): ?RgbColor> $parsers */
        $parsers = [
            'rgb('       => fn(string $content): ?RgbColor => $this->parseRgbFunction($content, false),
            'rgba('      => fn(string $content): ?RgbColor => $this->parseRgbFunction($content, true),
            'hsl('       => fn(string $content): ?RgbColor => $this->parseHslFunction($content, false),
            'hsla('      => fn(string $content): ?RgbColor => $this->parseHslFunction($content, true),
            'hwb('       => $this->parseHwbFunction(...),
            'lab('       => $this->parseLabFunction(...),
            'lch('       => $this->parseLchFunction(...),
            'oklab('     => $this->parseOklabFunction(...),
            'oklch('     => $this->parseOklchFunction(...),
            'color('     => $this->parseColorFunction(...),
            'color-mix(' => $this->parseColorMixFunction(...),
        ];

        foreach ($parsers as $prefix => $parser) {
            if (str_starts_with($value, $prefix) && str_ends_with($value, ')')) {
                $content = substr($value, strlen($prefix), -1);

                return $parser($content) ?? $black;
            }
        }

        return null;
    }

    private function parseRgbFunction(string $inner, bool $expectsAlpha): ?RgbColor
    {
        // Legacy syntax: uses commas (e.g., "255, 0, 0" or "255, 0, 0, 0.5")
        if (str_contains($inner, ',')) {
            $parsed = $this->parseLegacyThreeChannelFunction(
                $inner,
                $expectsAlpha,
                $this->parseRgbChannel(...),
                $this->parseRgbChannel(...),
                $this->parseRgbChannel(...)
            );

            if ($parsed === null) {
                return null;
            }

            [$red, $green, $blue, $alpha] = $parsed;

            return new RgbColor($red, $green, $blue, $alpha);
        }

        // Modern syntax: uses spaces with optional / alpha (e.g., "255 0 0" or "255 0 0 / 0.5")
        if (str_contains($inner, '/')) {
            [$parts, $alphaToken] = $this->splitSpaceSeparatedChannelsWithAlpha($inner, 3);

            if ($parts === null || $alphaToken === null) {
                return null;
            }

            $channels = $this->parseModernRgbChannels($parts);

            if ($channels === null) {
                return null;
            }

            $alpha = $this->parseAlpha($alphaToken);

            if ($alpha === null) {
                return null;
            }

            [$red, $green, $blue] = $channels;

            return new RgbColor($red, $green, $blue, $alpha);
        }

        // Modern syntax without alpha (e.g., "255 0 0" or "none 0 0")
        $parts = $this->splitWhitespace($inner);

        if (count($parts) !== 3) {
            return null;
        }

        $channels = $this->parseModernRgbChannels($parts);

        if ($channels === null) {
            return null;
        }

        [$red, $green, $blue] = $channels;

        return new RgbColor($red, $green, $blue, 1.0);
    }

    private function parseHslFunction(string $inner, bool $expectsAlpha): ?RgbColor
    {
        // Legacy syntax: uses commas (e.g., "210, 40%, 50%" or "210, 40%, 50%, 0.5")
        if (str_contains($inner, ',')) {
            $parsed = $this->parseLegacyThreeChannelFunction(
                $inner,
                $expectsAlpha,
                $this->parseHue(...),
                $this->parsePercent(...),
                $this->parsePercent(...)
            );

            if ($parsed === null) {
                return null;
            }

            [$hue, $saturation, $lightness, $alpha] = $parsed;

            return $this->createRgbFromHsl($hue, $saturation, $lightness, $alpha);
        }

        // Modern syntax: uses spaces with optional / alpha (e.g., "210 40% 50%" or "210 40% 50% / 0.5")
        return $this->parseSpaceSeparatedHueColorFunction($inner, true);
    }

    private function parseHwbFunction(string $inner): ?RgbColor
    {
        return $this->parseSpaceSeparatedHueColorFunction($inner, false);
    }

    private function parseLabFunction(string $inner): ?RgbColor
    {
        return $this->parseLabOklabFunction(
            $inner,
            fn(float $l, float $a, float $b, float $alpha): RgbColor => $this->labToRgb($l, $a, $b, $alpha),
            'lab'
        );
    }

    private function parseLchFunction(string $inner): ?RgbColor
    {
        return $this->parseThreeChannelFunction(
            $inner,
            fn(float $l, float $c, float $h, float $alpha): RgbColor => $this->lchToRgb($l, $c, $h, $alpha),
            fn(string $token): ?float => $this->parseNumeric($token)
        );
    }

    private function parseOklabFunction(string $inner): ?RgbColor
    {
        return $this->parseLabOklabFunction(
            $inner,
            fn(float $l, float $a, float $b, float $alpha): RgbColor => $this->oklabToRgb($l, $a, $b, $alpha),
            'oklab'
        );
    }

    private function parseOklchFunction(string $inner): ?RgbColor
    {
        return $this->parseThreeChannelFunction(
            $inner,
            fn(float $l, float $c, float $h, float $alpha): RgbColor => $this->oklchToRgb($l, $c, $h, $alpha),
            fn(string $token): ?float => $this->parseNumeric($token),
            true
        );
    }

    private function parseColorFunction(string $inner): ?RgbColor
    {
        [$parts, $alphaToken] = $this->splitSpaceSeparatedChannelsWithAlpha($inner, 4);

        if ($parts === null) {
            return null;
        }

        $space = strtolower($parts[0]);
        $c1    = $this->parseNumeric($parts[1]) ?? 0.0;
        $c2    = $this->parseNumeric($parts[2]) ?? 0.0;
        $c3    = $this->parseNumeric($parts[3]) ?? 0.0;

        if ($alphaToken !== null) {
            $alpha = $this->parseAlpha($alphaToken);
            if ($alpha === null) {
                return null;
            }
        } else {
            $alpha = 1.0;
        }

        $rgb = $this->colorSpaceRouter->convertToRgba($space, $c1, $c2, $c3, $alpha);

        return new RgbColor(
            r: $this->colorSpaceConverter->clamp($rgb->r ?? 0.0, 1.0),
            g: $this->colorSpaceConverter->clamp($rgb->g ?? 0.0, 1.0),
            b: $this->colorSpaceConverter->clamp($rgb->b ?? 0.0, 1.0),
            a: $rgb->a
        );
    }

    private function parseColorMixFunction(string $inner): ?RgbColor
    {
        $inner = trim($inner);

        if (! str_starts_with($inner, 'in ')) {
            return null;
        }

        $spaceEnd = strpos($inner, ',');
        if ($spaceEnd === false) {
            return null;
        }

        $spacePart  = substr($inner, 3, $spaceEnd - 3);
        $space      = strtolower(trim($spacePart));
        $colorsPart = trim(substr($inner, $spaceEnd + 1));

        $lastComma = strrpos($colorsPart, ',');
        if ($lastComma === false) {
            return null;
        }

        $color1Str = trim(substr($colorsPart, 0, $lastComma));
        $color2Str = trim(substr($colorsPart, $lastComma + 1));

        [$color1, $pct1] = $this->parseColorWithPercentage($color1Str);
        [$color2, $pct2] = $this->parseColorWithPercentage($color2Str);

        if ($color1 === null || $color2 === null) {
            return null;
        }

        $weight = $this->calculateMixWeight($pct1, $pct2);

        $resolver = new ColorMixResolver();

        return match($space) {
            'srgb',
            'srgb-linear' => $this->mixInSrgb($resolver, $color1, $color2, $weight),
            'hsl'         => $this->mixInHsl($resolver, $color1, $color2, $weight),
            'oklab'       => $this->mixInOklab($resolver, $color1, $color2, $weight),
            'oklch'       => $this->mixInOklch($resolver, $color1, $color2, $weight),
            'lab'         => $this->mixInLab($resolver, $color1, $color2, $weight),
            'lch'         => $this->mixInLch($resolver, $color1, $color2, $weight),
            default       => null,
        };
    }

    /**
     * @return array{0: ?list<string>, 1: ?string}
     */
    private function splitSpaceSeparatedChannelsWithAlpha(string $inner, int $expectedCount): array
    {
        [$channels, $alphaToken] = $this->splitSlash($inner);

        if ($channels === null) {
            return [null, null];
        }

        $parts = $this->splitWhitespace($channels);

        if (count($parts) !== $expectedCount) {
            return [null, null];
        }

        return [$parts, $alphaToken];
    }

    /**
     * @param Closure(string): ?float $parser1
     * @param Closure(string): ?float $parser2
     * @param Closure(string): ?float $parser3
     * @return array{0: float, 1: float, 2: float, 3: float}|null
     */
    private function parseLegacyThreeChannelFunction(
        string $inner,
        bool $expectsAlpha,
        Closure $parser1,
        Closure $parser2,
        Closure $parser3
    ): ?array {
        $parts = $this->splitCommaSeparated($inner);

        if (count($parts) !== ($expectsAlpha ? 4 : 3)) {
            return null;
        }

        $value1 = $parser1($parts[0]);
        $value2 = $parser2($parts[1]);
        $value3 = $parser3($parts[2]);

        if ($value1 === null || $value2 === null || $value3 === null) {
            return null;
        }

        if (! $expectsAlpha) {
            return [$value1, $value2, $value3, 1.0];
        }

        $alpha = $this->parseAlpha($parts[3]);

        if ($alpha === null) {
            return null;
        }

        return [$value1, $value2, $value3, $alpha];
    }

    /**
     * @param list<string> $parts
     * @return array{0: float, 1: float, 2: float}|null
     */
    private function parseModernRgbChannels(array $parts): ?array
    {
        if (
            ! $this->isValidRgbToken($parts[0])
            || ! $this->isValidRgbToken($parts[1])
            || ! $this->isValidRgbToken($parts[2])
        ) {
            return null;
        }

        return [
            $this->parseRgbChannel($parts[0]) ?? 0.0,
            $this->parseRgbChannel($parts[1]) ?? 0.0,
            $this->parseRgbChannel($parts[2]) ?? 0.0,
        ];
    }

    private function createRgbFromHsl(
        ?float $hue,
        ?float $saturation,
        ?float $lightness,
        float $alpha
    ): RgbColor {
        [$red, $green, $blue] = $this->colorSpaceConverter->hslToRgb(
            $hue ?? 0.0,
            $saturation ?? 0.0,
            $lightness ?? 0.0
        );

        return new RgbColor($red, $green, $blue, $alpha);
    }

    /**
     * @param Closure(float, float, float, float): RgbColor $toRgb
     * @param Closure(string): ?float $channelParser
     */
    private function parseThreeChannelFunction(
        string $inner,
        Closure $toRgb,
        Closure $channelParser,
        bool $parseHueForThird = false
    ): ?RgbColor {
        if (str_contains($inner, '/')) {
            [$parts, $alphaToken] = $this->splitSpaceSeparatedChannelsWithAlpha($inner, 3);

            if ($parts === null || $alphaToken === null) {
                return null;
            }

            $alpha = $this->parseAlpha($alphaToken);

            if ($alpha === null) {
                return null;
            }

            $c1 = $channelParser($parts[0]) ?? 0.0;
            $c2 = $channelParser($parts[1]) ?? 0.0;
            $c3 = $parseHueForThird
                ? ($this->parseHue($parts[2]) ?? 0.0)
                : ($channelParser($parts[2]) ?? 0.0);

            return $toRgb($c1, $c2, $c3, $alpha);
        }

        $parts = $this->splitCommaSeparated($inner);

        if (count($parts) !== 3) {
            $parts = $this->splitWhitespace($inner);
            if (count($parts) !== 3) {
                return null;
            }
        }

        $c1 = $channelParser($parts[0]) ?? 0.0;
        $c2 = $channelParser($parts[1]) ?? 0.0;
        $c3 = $parseHueForThird
            ? ($this->parseHue($parts[2]) ?? 0.0)
            : ($channelParser($parts[2]) ?? 0.0);

        return $toRgb($c1, $c2, $c3, 1.0);
    }

    /**
     * @param Closure(float, float, float, float): RgbColor $toRgb
     */
    private function parseLabOklabFunction(string $inner, Closure $toRgb, string $space): ?RgbColor
    {
        $channelIndex = 0;

        return $this->parseThreeChannelFunction(
            $inner,
            $toRgb,
            function (string $token) use ($space, &$channelIndex): ?float {
                $result = $this->parseLabChannel($token, $space, $channelIndex);

                $channelIndex++;

                return $result;
            }
        );
    }

    private function parseLabChannel(string $token, string $space, int $channelIndex): ?float
    {
        if ($token === '' || strtolower($token) === 'none') {
            return null;
        }

        if (str_ends_with($token, '%')) {
            $number = $this->parseNumeric(substr($token, 0, -1));

            if ($number === null) {
                return null;
            }

            if ($channelIndex === 0) {
                return $number;
            }

            if ($space === 'lab') {
                return ($number / 100.0) * 125.0;
            }

            if ($space === 'oklab') {
                return ($number / 100.0) * 0.4;
            }
        }

        return $this->parseNumeric($token);
    }

    /**
     * @return array{0: RgbColor|null, 1: float|null}
     */
    private function parseColorWithPercentage(string $colorStr): array
    {
        $colorStr   = trim($colorStr);
        $percentPos = strrpos($colorStr, '%');
        $percentage = null;

        if ($percentPos !== false) {
            $numStart = $percentPos - 1;
            while ($numStart >= 0 && (ctype_digit($colorStr[$numStart]) || $colorStr[$numStart] === '.')) {
                $numStart--;
            }

            $numStart++;

            $percentStr = substr($colorStr, $numStart, $percentPos - $numStart);
            $percentage = (float) $percentStr / 100.0;
            $colorStr   = trim(substr($colorStr, 0, $numStart) . substr($colorStr, $percentPos + 1));
        }

        $color = $this->parseColorString($colorStr);

        return [$color, $percentage];
    }

    private function parseColorString(string $colorStr): ?RgbColor
    {
        $colorStr = trim($colorStr);

        if (str_starts_with($colorStr, '#')) {
            return (new LiteralParser())->toRgb($colorStr);
        }

        $lower = strtolower($colorStr);
        if (array_key_exists($lower, NamedColors::NAMED_RGB)) {
            $named = NamedColors::NAMED_RGB[$lower];

            return new RgbColor(
                r: $named[0] / 255.0,
                g: $named[1] / 255.0,
                b: $named[2] / 255.0,
                a: $named[3] ?? 1.0
            );
        }

        return $this->convertSupportedFunctionalColorToRgb($colorStr);
    }

    private function calculateMixWeight(?float $pct1, ?float $pct2): float
    {
        if ($pct1 !== null && $pct2 !== null) {
            $total = $pct1 + $pct2;
            if ($total > 0.0) {
                return $pct1 / $total;
            }

            return 0.5;
        }

        if ($pct1 !== null) {
            return $pct1;
        }

        if ($pct2 !== null) {
            return 1.0 - $pct2;
        }

        return 0.5;
    }

    private function mixInSrgb(
        ColorMixResolver $resolver,
        RgbColor $color1,
        RgbColor $color2,
        float $weight
    ): RgbColor {
        return $resolver->mixSrgb($color1, $color2, $weight);
    }

    private function mixInHsl(
        ColorMixResolver $resolver,
        RgbColor $color1,
        RgbColor $color2,
        float $weight
    ): RgbColor {
        $hsl1 = $this->rgbToHsl($color1);
        $hsl2 = $this->rgbToHsl($color2);

        $mixed = $resolver->mixHsl($hsl1, $hsl2, $weight);

        return $this->hslToRgb($mixed);
    }

    private function mixInOklab(
        ColorMixResolver $resolver,
        RgbColor $color1,
        RgbColor $color2,
        float $weight
    ): RgbColor {
        $oklab1 = $this->colorSpaceConverter->normalizedRgbToOklch($color1, false);
        $oklab2 = $this->colorSpaceConverter->normalizedRgbToOklch($color2, false);

        $oklabColor1 = new OklabColor(
            l: $oklab1->l,
            a: ($oklab1->c ?? 0.0) * cos(($oklab1->h ?? 0.0) * M_PI / 180.0),
            b: ($oklab1->c ?? 0.0) * sin(($oklab1->h ?? 0.0) * M_PI / 180.0),
            alpha: $color1->a
        );

        $oklabColor2 = new OklabColor(
            l: $oklab2->l,
            a: ($oklab2->c ?? 0.0) * cos(($oklab2->h ?? 0.0) * M_PI / 180.0),
            b: ($oklab2->c ?? 0.0) * sin(($oklab2->h ?? 0.0) * M_PI / 180.0),
            alpha: $color2->a
        );

        $mixed = $resolver->mixOklab($oklabColor1, $oklabColor2, $weight);

        $rgb = $this->colorSpaceConverter->oklabToSrgb($mixed->l ?? 0.0, $mixed->a ?? 0.0, $mixed->b ?? 0.0);

        return new RgbColor(
            r: $rgb[0],
            g: $rgb[1],
            b: $rgb[2],
            a: $mixed->alpha
        );
    }

    private function mixInOklch(
        ColorMixResolver $resolver,
        RgbColor $color1,
        RgbColor $color2,
        float $weight
    ): RgbColor {
        $oklch1 = $this->colorSpaceConverter->normalizedRgbToOklch($color1, false);
        $oklch2 = $this->colorSpaceConverter->normalizedRgbToOklch($color2, false);

        $oklchColor1 = new OklchColor(l: $oklch1->l, c: $oklch1->c, h: $oklch1->h, a: $color1->a);
        $oklchColor2 = new OklchColor(l: $oklch2->l, c: $oklch2->c, h: $oklch2->h, a: $color2->a);

        $mixed = $resolver->mixOklch($oklchColor1, $oklchColor2, $weight);

        $rgb = $this->colorSpaceConverter->oklchToSrgbUnclamped($mixed);

        return new RgbColor(r: $rgb->r, g: $rgb->g, b: $rgb->b, a: $mixed->a);
    }

    private function mixInLab(
        ColorMixResolver $resolver,
        RgbColor $color1,
        RgbColor $color2,
        float $weight
    ): RgbColor {
        $lab1 = $this->rgbToLab($color1);
        $lab2 = $this->rgbToLab($color2);

        $mixed = $resolver->mixLab($lab1, $lab2, $weight);

        return $this->labToRgb($mixed->l ?? 0.0, $mixed->a ?? 0.0, $mixed->b ?? 0.0, $mixed->alpha);
    }

    private function mixInLch(
        ColorMixResolver $resolver,
        RgbColor $color1,
        RgbColor $color2,
        float $weight
    ): RgbColor {
        $lch1 = $this->rgbToLch($color1);
        $lch2 = $this->rgbToLch($color2);

        $mixed = $resolver->mixLch($lch1, $lch2, $weight);

        return $this->lchToRgb($mixed->l ?? 0.0, $mixed->c ?? 0.0, $mixed->h ?? 0.0, $mixed->alpha);
    }

    private function rgbToHsl(RgbColor $rgb): HslColor
    {
        $r = $rgb->r ?? 0.0;
        $g = $rgb->g ?? 0.0;
        $b = $rgb->b ?? 0.0;

        $max   = max($r, $g, $b);
        $min   = min($r, $g, $b);
        $delta = $max - $min;

        $l = ($max + $min) / 2.0;

        if ($delta === 0.0) {
            $h = 0.0;
            $s = 0.0;
        } else {
            $s = $delta / (1.0 - abs(2.0 * $l - 1.0));

            if ($max === $r) {
                $h = 60.0 * fmod(($g - $b) / $delta, 6.0);
            } elseif ($max === $g) {
                $h = 60.0 * ((($b - $r) / $delta) + 2.0);
            } else {
                $h = 60.0 * ((($r - $g) / $delta) + 4.0);
            }

            if ($h < 0.0) {
                $h += 360.0;
            }
        }

        return new HslColor(h: $h, s: $s * 100.0, l: $l * 100.0, a: $rgb->a);
    }

    private function hslToRgb(HslColor $hsl): RgbColor
    {
        $h = $hsl->h ?? 0.0;
        $s = ($hsl->s ?? 0.0) / 100.0;
        $l = ($hsl->l ?? 0.0) / 100.0;

        [$r, $g, $b] = $this->colorSpaceConverter->hslToRgb($h, $s, $l);

        return new RgbColor(r: $r, g: $g, b: $b, a: $hsl->a);
    }

    private function rgbToLab(RgbColor $rgb): LabColor
    {
        $xyz = $this->colorSpaceConverter->rgbToXyzD50($rgb);

        [$l, $a, $b] = $this->colorSpaceConverter->xyzToLabD50($xyz);

        return new LabColor(l: $l, a: $a, b: $b, alpha: $rgb->a);
    }

    private function rgbToLch(RgbColor $rgb): LchColor
    {
        $xyz = $this->colorSpaceConverter->rgbToXyzD50($rgb);
        $lch = $this->colorSpaceConverter->xyzD50ToLch($xyz);

        return new LchColor(l: $lch->l, c: $lch->c, h: $lch->h, alpha: $rgb->a);
    }

    private function parseSpaceSeparatedHueColorFunction(string $inner, bool $isHsl): ?RgbColor
    {
        [$parts, $alphaToken] = $this->splitSpaceSeparatedChannelsWithAlpha($inner, 3);

        if ($parts === null) {
            return null;
        }

        $hue           = $this->parseHue($parts[0]) ?? 0.0;
        $firstPercent  = $this->parsePercent($parts[1]);
        $secondPercent = $this->parsePercent($parts[2]);

        if ($alphaToken !== null) {
            $alpha = $this->parseAlpha($alphaToken);
            if ($alpha === null) {
                return null;
            }
        } else {
            $alpha = 1.0;
        }

        if ($firstPercent === null || $secondPercent === null) {
            return null;
        }

        [$red, $green, $blue] = $isHsl
            ? $this->colorSpaceConverter->hslToRgb($hue, $firstPercent, $secondPercent)
            : $this->colorSpaceConverter->hwbToRgb($hue, $firstPercent, $secondPercent);

        return new RgbColor($red, $green, $blue, $alpha);
    }

    private function isLegacyColorWithNone(string $value): bool
    {
        if (
            ! str_contains(strtolower($value), 'none')
            || ! str_contains($value, ',')
            || ! str_ends_with($value, ')')
        ) {
            return false;
        }

        foreach (['rgb(', 'rgba(', 'hsl(', 'hsla('] as $prefix) {
            if (str_starts_with($value, $prefix)) {
                $inner = substr($value, strlen($prefix), -1);

                return str_contains($inner, ',') && str_contains(strtolower($inner), 'none');
            }
        }

        return false;
    }

    private function encodeHex(RgbColor $rgb): string
    {
        $red   = $this->roundAndClampByte(($rgb->r ?? 0.0) * 255.0);
        $green = $this->roundAndClampByte(($rgb->g ?? 0.0) * 255.0);
        $blue  = $this->roundAndClampByte(($rgb->b ?? 0.0) * 255.0);

        $hex = $this->roundAndClampByte($rgb->a * 255.0) === 255
            ? $this->hexColorEncoder->encodeRgb($red, $green, $blue)
            : $this->hexColorEncoder->encodeRgba(
                $red,
                $green,
                $blue,
                $this->roundAndClampByte($rgb->a * 255.0),
            );

        return $this->hexColorNormalizer->normalize($hex) ?? $hex;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitSlash(string $value): array
    {
        $slashAt = null;
        $length  = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            if ($value[$i] === '/') {
                if ($slashAt !== null) {
                    return [null, null];
                }

                $slashAt = $i;
            }
        }

        if ($slashAt === null) {
            return [trim($value), null];
        }

        return [
            trim(substr($value, 0, $slashAt)),
            trim(substr($value, $slashAt + 1)),
        ];
    }

    /**
     * @return list<string>
     */
    private function splitWhitespace(string $value): array
    {
        $items   = [];
        $current = '';
        $length  = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if (ctype_space($char)) {
                if ($current !== '') {
                    $items[] = $current;
                    $current = '';
                }

                continue;
            }

            $current .= $char;
        }

        if ($current !== '') {
            $items[] = $current;
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function splitCommaSeparated(string $value): array
    {
        $parts = explode(',', $value);

        foreach ($parts as $index => $part) {
            $parts[$index] = trim($part);
        }

        return $parts;
    }

    private function parseRgbChannel(string $token): ?float
    {
        if ($token === '' || strtolower($token) === 'none') {
            return null;
        }

        if (str_ends_with($token, '%')) {
            $number = $this->parseNumeric(substr($token, 0, -1));

            if ($number === null) {
                return null;
            }

            return $number / 100.0;
        }

        $number = $this->parseNumeric($token);

        if ($number === null) {
            return null;
        }

        return $number / 255.0;
    }

    private function isValidRgbToken(string $token): bool
    {
        $lower = strtolower($token);

        if ($lower === 'none') {
            return true;
        }

        if (str_ends_with($lower, '%')) {
            $num = substr($lower, 0, -1);

            return $num !== '' && is_numeric($num);
        }

        return $token !== '' && is_numeric($token);
    }

    private function parsePercent(string $token): ?float
    {
        if (strtolower($token) === 'none') {
            return 0.0;
        }

        if (! str_ends_with($token, '%')) {
            return null;
        }

        $number = $this->parseNumeric(substr($token, 0, -1));

        if ($number === null) {
            return null;
        }

        return $number / 100.0;
    }

    private function parseAlpha(string $token): ?float
    {
        if ($token === '') {
            return null;
        }

        if (strtolower($token) === 'none') {
            return 0.0;
        }

        if (str_ends_with($token, '%')) {
            $number = $this->parseNumeric(substr($token, 0, -1));

            if ($number === null) {
                return null;
            }

            return $number / 100.0;
        }

        return $this->parseNumeric($token);
    }

    private function parseHue(string $token): ?float
    {
        if ($token === '' || strtolower($token) === 'none') {
            return null;
        }

        $lower = strtolower($token);

        if (str_ends_with($lower, 'deg')) {
            return $this->parseNumeric(substr($lower, 0, -3));
        }

        if (str_ends_with($lower, 'turn')) {
            $number = $this->parseNumeric(substr($lower, 0, -4));

            return $number === null ? null : $number * 360.0;
        }

        if (str_ends_with($lower, 'grad')) {
            $number = $this->parseNumeric(substr($lower, 0, -4));

            return $number === null ? null : $number * 0.9;
        }

        if (str_ends_with($lower, 'rad')) {
            $number = $this->parseNumeric(substr($lower, 0, -3));

            return $number === null ? null : $number * (180.0 / M_PI);
        }

        return $this->parseNumeric($lower);
    }

    private function parseNumeric(string $token): ?float
    {
        $token = trim($token);

        if ($token === '' || strtolower($token) === 'none' || ! is_numeric($token)) {
            return null;
        }

        return (float) $token;
    }

    private function roundAndClampByte(float $value): int
    {
        $byte = (int) round($value, 0, PHP_ROUND_HALF_UP);

        if ($byte < 0) {
            return 0;
        }

        if ($byte > 255) {
            return 255;
        }

        return $byte;
    }

    private function labToRgb(float $l, float $a, float $b, float $alpha): RgbColor
    {
        [$r, $g, $b] = $this->colorSpaceConverter->labToSrgb($l, $a, $b);

        return new RgbColor(
            r: $this->colorSpaceConverter->clamp($r, 1.0),
            g: $this->colorSpaceConverter->clamp($g, 1.0),
            b: $this->colorSpaceConverter->clamp($b, 1.0),
            a: $alpha
        );
    }

    private function lchToRgb(float $l, float $c, float $h, float $alpha): RgbColor
    {
        $hueRad = $h * M_PI / 180.0;

        $a = $c * cos($hueRad);
        $b = $c * sin($hueRad);

        return $this->labToRgb($l, $a, $b, $alpha);
    }

    private function oklabToRgb(float $l, float $a, float $b, float $alpha): RgbColor
    {
        $rgb = $this->colorSpaceConverter->oklabToSrgb($l, $a, $b);

        return new RgbColor(
            r: $this->colorSpaceConverter->clamp($rgb[0], 1.0),
            g: $this->colorSpaceConverter->clamp($rgb[1], 1.0),
            b: $this->colorSpaceConverter->clamp($rgb[2], 1.0),
            a: $alpha
        );
    }

    private function oklchToRgb(float $l, float $c, float $h, float $alpha): RgbColor
    {
        $hueRad = $h * M_PI / 180.0;

        $a = $c * cos($hueRad);
        $b = $c * sin($hueRad);

        return $this->oklabToRgb($l, $a, $b, $alpha);
    }
}
