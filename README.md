# Iris

**Color space conversions, serialization, and operations for legacy and modern CSS colors.**

Named after Iris, the goddess of the rainbow in Greek mythology.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require bugo/iris
```

## Color spaces

Iris supports the legacy CSS spaces and the modern color spaces used by `lab()`, `lch()`, `oklab()`, `oklch()`, and `color(...)`.

| Class        | Space     | Channels                                 |
|--------------|-----------|------------------------------------------|
| `RgbColor`   | `rgb`     | r, g, b (0-255), a (0-1)                 |
| `HslColor`   | `hsl`     | h (0-360), s (0-100), l (0-100), a (0-1) |
| `HwbColor`   | `hwb`     | h (0-360), w (0-100), b (0-100), a (0-1) |
| `LabColor`   | `lab`     | l (0-100), a, b, alpha (0-1)             |
| `LchColor`   | `lch`     | l (0-100), c, h (0-360), alpha (0-1)     |
| `OklabColor` | `oklab`   | l (0-1), a, b, alpha (0-1)               |
| `OklchColor` | `oklch`   | l (0-100), c, h (0-360), a (0-1)         |
| `XyzColor`   | `xyz-*`   | x, y, z                                  |

`XyzColor` is reused for both `xyz-d65` and `xyz-d50`; the white point depends on the method or route you call.

## Channel scales and conventions

Iris does not use one universal channel scale across every API surface.

- Object API: methods that accept `RgbColor`, `HslColor`, `LabColor`, `OklchColor`, and other space objects follow each object's native scale. For example, `RgbColor` stores byte-like channels (`0-255`), `HslColor` and `HwbColor` use percentage-like `0-100` channels, `OklchColor` stores lightness on a `0-100` scale, and `OklabColor` keeps lightness normalized to `0-1`.
- Channel API: methods whose names contain `Channels`, such as `srgbChannelsToXyzD65()`, `oklchChannelsToSrgba()`, or `labChannelsToXyzD65()`, form the normalized low-level API. These methods accept math-oriented channel values and usually return normalized floats or typed objects built from those normalized channels.
- `SpaceRouter`: routes by string space name on top of the channel API. It is best suited for CSS `color(<space> ...)` flows, where spaces such as `srgb`, `display-p3`, `rec2020`, and `xyz-*` are passed around as normalized channel triples. `lab`, `lch`, `oklab`, and `oklch` are also accepted for symmetry, but the typed `SpaceConverter` methods are usually clearer when you already know the target space at compile time.

## When to use what

- `SpaceConverter`: typed color math and direct conversions between concrete spaces.
- `SpaceRouter`: dispatch from a string space name to `RGBA` or `XYZ D65`.
- `Serializer`: normalize CSS color strings and optionally convert supported functions to hex.
- `CssSerializer`: serialize typed color objects back to CSS functions.
- `LiteralConverter` and `LiteralSerializer`: convert hex and named colors to or from `RgbColor`.
- `LegacyManipulator`, `PerceptualManipulator`, `SrgbManipulator`: adjust, mix, and transform colors at different abstraction levels.

## Usage

### Creating color objects

```php
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;

$red   = new RgbColor(r: 255.0, g: 0.0, b: 0.0, a: 1.0);
$green = new HslColor(h: 120.0, s: 100.0, l: 50.0, a: 1.0);
$blue  = new OklchColor(l: 45.2, c: 31.3, h: 264.1, a: 1.0);
```

### Converting between color spaces

```php
use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Spaces\RgbColor;

$converter = new SpaceConverter();
$rgb = new RgbColor(r: 255.0, g: 128.0, b: 0.0, a: 1.0);

// RGB -> OKLCh object
$oklch = $converter->rgbToOklch($rgb);
echo $oklch->l; // ~70 on the object scale
echo $oklch->h; // ~55

// RGB -> XYZ D65 object
$xyz = $converter->rgbToXyzD65($rgb);
echo $xyz->x;

// HSL channels -> RGB channels (returns [r, g, b] as normalized floats)
[$r, $g, $b] = $converter->hslToRgb(30.0, 1.0, 0.5);

// Normalized channel API -> XYZ D65
$xyzFromChannels = $converter->srgbChannelsToXyzD65(1.0, 0.5, 0.0);
```

The `*Channels*` methods are the normalized channel API. Methods that accept color objects such as `RgbColor` or `OklchColor` remain object-oriented entry points.

### Routing by space name

```php
use Bugo\Iris\SpaceRouter;
use Bugo\Iris\Exceptions\UnsupportedColorSpace;

$router = new SpaceRouter();

try {
    $rgba = $router->convertToRgba('display-p3', 1.0, 0.5, 0.0, 1.0);
    echo $rgba->r; // normalized 0-1
} catch (UnsupportedColorSpace $e) {
    // unknown color space
}

$xyz = $router->convertToXyzD65('rec2020', 0.4, 0.3, 0.2);
echo $xyz->y;
```

### Color manipulations

```php
use Bugo\Iris\Manipulators\LegacyManipulator;
use Bugo\Iris\Spaces\RgbColor;

$manipulator = new LegacyManipulator();
$color = new RgbColor(r: 200.0, g: 100.0, b: 50.0, a: 1.0);

$gray       = $manipulator->grayscale($color);
$mixed      = $manipulator->mix($color, new RgbColor(0.0, 150.0, 255.0, 1.0), 0.5);
$darker     = $manipulator->darken($color, 10.0);
$saturated  = $manipulator->saturate($color, 20.0);
$rotated    = $manipulator->spin($color, 30.0);
```

### Gamut mapping

`GamutMapper` maps out-of-sRGB-gamut colors back into gamut using two algorithms from CSS Color Level 4.

```php
use Bugo\Iris\Operations\GamutMapper;
use Bugo\Iris\Spaces\OklchColor;

$mapper = new GamutMapper();
$oklch = new OklchColor(l: 70.0, c: 40.0, h: 30.0, a: 1.0);

$clipped = $mapper->clip($oklch);
$mapped = $mapper->localMinde($oklch);
```

Both methods accept and return `OklchColor`. For other spaces, convert to `OklchColor` first.

### CSS `color-mix()` interpolation

`ColorMixResolver` implements CSS Color Level 4 interpolation rules, including `none` channel handling and all four hue interpolation methods.

```php
use Bugo\Iris\Operations\ColorMixResolver;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;

$resolver = new ColorMixResolver();

$mixSrgb = $resolver->mixSrgb(
    new RgbColor(r: 255.0, g: 0.0, b: 0.0, a: 1.0),
    new RgbColor(r: 0.0, g: 0.0, b: 255.0, a: 1.0),
    0.5,
);

$mixOklch = $resolver->mixOklch(
    new OklchColor(l: 70.0, c: 20.0, h: 30.0, a: 1.0),
    new OklchColor(l: 50.0, c: 10.0, h: 200.0, a: 1.0),
    0.5,
    hueMethod: 'shorter',
);
```

If one side uses `null` for a channel, the other side wins instead of interpolating. If both sides are `null`, the result stays `null`.

### Hex encoding

```php
use Bugo\Iris\Encoders\HexEncoder;
use Bugo\Iris\Encoders\HexNormalizer;
use Bugo\Iris\Encoders\HexShortener;

$encoder = new HexEncoder();
$shortener = new HexShortener();
$normalizer = new HexNormalizer();

$hex = $encoder->encodeRgb(255, 128, 0);        // '#ff8000'
$hexA = $encoder->encodeRgba(255, 128, 0, 255); // '#ff8000ff'
$short = $shortener->shorten('#aabbcc');        // '#abc'
$norm = $normalizer->normalize('#AABBCC');      // '#abc'
```

### Parsing CSS color literals

```php
use Bugo\Iris\LiteralParser;
use Bugo\Iris\Serializers\LiteralSerializer;
use Bugo\Iris\Spaces\RgbColor;

$converter = new LiteralParser();
$serializer = new LiteralSerializer();

$rgbFromHex = $converter->toRgb('#ff8000');
$rgbFromName = $converter->toRgb('tomato');

echo $serializer->serialize(new RgbColor(r: 255.0, g: 0.0, b: 0.0, a: 1.0));
echo $serializer->serialize(new RgbColor(r: 255.0, g: 128.0, b: 0.0, a: 1.0));
```

### `Serializer` vs `CssSerializer`

Use `Serializer` when the input is already a CSS string and you want normalization or optional hex conversion.

```php
use Bugo\Iris\Serializers\Serializer;

$serializer = new Serializer();

echo $serializer->serialize('#AABBCC', false);          // '#abc'
echo $serializer->serialize('rgb(255, 128, 0)', true); // '#ff8000'
echo $serializer->serialize('rgb(255, 128, 0)', false); // 'rgb(255, 128, 0)'
```

Use `CssSerializer` when the input is a typed color object and you want a CSS function string.

```php
use Bugo\Iris\Serializers\CssSerializer;
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\LchColor;
use Bugo\Iris\Spaces\OklabColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\XyzColor;

$serializer = new CssSerializer();
$oklch = new OklchColor(l: 70.0, c: 15.0, h: 55.0, a: 1.0);

echo $serializer->toCss($oklch);       // 'oklch(70 15 55)'
echo $serializer->toCss($oklch, true); // still serialized as a CSS color string

$hsl = new HslColor(h: 30.0, s: 100.0, l: 50.0, a: 0.8);
$lab = new LabColor(l: 50.0, a: 20.0, b: -30.0, alpha: 1.0);
$lch = new LchColor(l: 70.0, c: 30.0, h: 180.0, alpha: 1.0);
$oklab = new OklabColor(l: 0.5, a: 0.1, b: -0.05, alpha: 1.0);
$xyz = new XyzColor(x: 0.9505, y: 1.0, z: 1.0890);

echo $serializer->toCss($hsl);   // 'hsl(30 100% 50% / 0.80)'
echo $serializer->toCss($lab);   // 'lab(50% 20 -30)'
echo $serializer->toCss($lch);   // 'lch(70% 30 180)'
echo $serializer->toCss($oklab); // 'oklab(0.5 0.1 -0.05)'
echo $serializer->toCss($xyz);   // 'color(xyz-d65 0.9505 1 1.089)'
```

If you specifically need hex from an `RgbColor`, call `CssSerializer::toHex()` or `LiteralSerializer`.

### Model conversion

```php
use Bugo\Iris\Converters\ModelConverter;
use Bugo\Iris\Spaces\RgbColor;

$converter = new ModelConverter();
$rgb = new RgbColor(r: 255.0, g: 128.0, b: 0.0, a: 1.0);
$hsl = $converter->rgbToHslColor($rgb);
$rgbBack = $converter->hslToRgbColor($hsl);
```

### Perceptual manipulations

```php
use Bugo\Iris\Manipulators\PerceptualManipulator;
use Bugo\Iris\Spaces\LabColor;
use Bugo\Iris\Spaces\OklchColor;

$manipulator = new PerceptualManipulator();

$adjusted = $manipulator->adjustOklch(
    new OklchColor(l: 70.0, c: 15.0, h: 55.0, a: 1.0),
    ['lightness' => 10.0, 'chroma' => -5.0, 'hue' => 20.0],
);

$labChanged = $manipulator->changeLab(
    new LabColor(l: 50.0, a: 20.0, b: -30.0, alpha: 1.0),
    ['lightness' => 70.0, 'alpha' => 0.5],
);
```

### Linear RGB manipulations

```php
use Bugo\Iris\Manipulators\SrgbManipulator;

$manipulator = new SrgbManipulator();

$adjusted = $manipulator->adjust(
    red: 1.0,
    green: 0.5,
    blue: 0.0,
    values: ['red' => -0.1, 'green' => 0.1, 'blue' => 0.05],
);
```

### Wide-gamut color spaces

```php
use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Spaces\RgbColor;
use Bugo\Iris\Spaces\XyzColor;

$converter = new SpaceConverter();
$rgb = new RgbColor(r: 255.0, g: 128.0, b: 0.0, a: 1.0);

[$p3R, $p3G, $p3B] = $converter->rgbToDisplayP3($rgb);
$a98 = $converter->rgbToA98Rgb($rgb);
$prophoto = $converter->rgbToProphotoRgb($rgb);
$rec2020 = $converter->rgbToRec2020($rgb);

$xyz = new XyzColor(x: 0.5, y: 0.4, z: 0.2);
$p3FromXyz = $converter->xyzD65ToDisplayP3($xyz);
```

### Polar math utilities

```php
use Bugo\Iris\Operations\PolarMath;

$math = new PolarMath();
[$a, $b] = $math->toCartesian(chroma: 0.2, hue: 55.0);
$radians = $math->toRadians(180.0); // pi
```

### Named colors

```php
use Bugo\Iris\NamedColors;

$tomatoRgb = NamedColors::NAMED_RGB['tomato']; // [255.0, 99.0, 71.0]
$redRgb = NamedColors::NAMED_RGB['red'];       // [255.0, 0.0, 0.0]

$hex = NamedColors::toHex('tomato');      // '#ff6347'
$hex = NamedColors::toHex('transparent'); // '#00000000'

NamedColors::isNamedColor('tomato'); // true
$names = NamedColors::getNames();
```

`NamedColors::NAMED_RGB` stores byte-like channel values, not normalized `0-1` floats.

### ColorValueInterface

All `Spaces/*` classes implement `Bugo\Iris\Contracts\ColorValueInterface`.

```php
use Bugo\Iris\Contracts\ColorValueInterface;
use Bugo\Iris\Spaces\OklchColor;

function describeColor(ColorValueInterface $color): string
{
    return sprintf(
        'Space: %s, channels: [%s], alpha: %s',
        $color->getSpace(),
        implode(', ', $color->getChannels()),
        $color->getAlpha(),
    );
}

echo describeColor(new OklchColor(l: 70.0, c: 15.0, h: 55.0, a: 1.0));
```

## Exceptions

```php
use Bugo\Iris\Exceptions\IrisException;
use Bugo\Iris\Exceptions\InvalidColorChannel;
use Bugo\Iris\Exceptions\InvalidColorFormat;
use Bugo\Iris\Exceptions\UnsupportedColorSpace;
```

All exceptions extend `IrisException`, which extends `\RuntimeException`.

- `UnsupportedColorSpace`: unknown space passed to `SpaceRouter` or other string-based conversion entry points.
- `InvalidColorFormat`: malformed CSS color literals, unsupported function syntax, or invalid serialization input.
- `InvalidColorChannel`: out-of-domain or malformed channel values for APIs that validate channel content.

## Comparison with other implementations

See [comparisons_results.md](comparisons_results.md) for the results.

## Useful links

* https://www.w3.org/TR/css-color-4/
