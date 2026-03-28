# Iris

![PHP](https://img.shields.io/badge/PHP-^8.2-blue.svg?style=flat)
[![Coverage Status](https://coveralls.io/repos/github/dragomano/iris/badge.svg?branch=main)](https://coveralls.io/github/dragomano/iris?branch=main)

**Конвертация цветовых пространств, сериализация и операции над устаревшими и современными CSS-цветами.**

Названа в честь Ириды, богини радуги в древнегреческой мифологии.

## Установка

```bash
composer require bugo/iris
```

## Цветовые пространства

Iris поддерживает устаревшие CSS-пространства и современные пространства, используемые в `lab()`, `lch()`, `oklab()`, `oklch()` и `color(...)`.

| Класс        | Пространство | Каналы                                   |
|--------------|--------------|------------------------------------------|
| `RgbColor`   | `rgb`        | r, g, b (0-255), a (0-1)                 |
| `HslColor`   | `hsl`        | h (0-360), s (0-100), l (0-100), a (0-1) |
| `HwbColor`   | `hwb`        | h (0-360), w (0-100), b (0-100), a (0-1) |
| `LabColor`   | `lab`        | l (0-100), a, b, alpha (0-1)             |
| `LchColor`   | `lch`        | l (0-100), c, h (0-360), alpha (0-1)     |
| `OklabColor` | `oklab`      | l (0-1), a, b, alpha (0-1)               |
| `OklchColor` | `oklch`      | l (0-100), c, h (0-360), a (0-1)         |
| `XyzColor`   | `xyz-*`      | x, y, z                                  |

`XyzColor` используется и для `xyz-d65`, и для `xyz-d50`; конкретная белая точка зависит от вызываемого метода или маршрута.

## Шкалы каналов и соглашения

В Iris нет одной универсальной шкалы каналов для всех API.

- Объектный API: методы, принимающие `RgbColor`, `HslColor`, `LabColor`, `OklchColor` и другие объекты пространств, используют собственную шкалу соответствующего объекта. Например, `RgbColor` хранит байтовые каналы (`0-255`), `HslColor` и `HwbColor` используют каналы `0-100`, `OklchColor` хранит светлоту по шкале `0-100`, а `OklabColor` использует нормализованную светлоту `0-1`.
- Канальный API: методы, в имени которых есть `Channels`, например `srgbChannelsToXyzD65()`, `oklchChannelsToSrgba()` или `labChannelsToXyzD65()`, образуют нормализованный низкоуровневый API. Они принимают каналы, удобные для математических преобразований, и обычно возвращают нормализованные `float`-значения или типизированные объекты, собранные из таких каналов.
- `SpaceRouter`: выполняет маршрутизацию по строковому имени пространства поверх канального API. Он лучше всего подходит для сценариев с CSS `color(<space> ...)`, где пространства вроде `srgb`, `display-p3`, `rec2020` и `xyz-*` передаются как нормализованные тройки каналов. `lab`, `lch`, `oklab` и `oklch` тоже поддерживаются для симметрии, но если целевое пространство известно заранее, типизированные методы `SpaceConverter` обычно понятнее.

## Когда использовать что

- `SpaceConverter`: типизированная математика и прямые конвертации между конкретными пространствами.
- `SpaceRouter`: диспетчеризация по строковому имени пространства в `RGBA` или `XYZ D65`.
- `Serializer`: нормализация CSS-строк с цветами и опциональная конвертация поддержанных функций в hex.
- `CssSerializer`: сериализация типизированных объектов цвета обратно в CSS-функции.
- `LiteralConverter` и `LiteralSerializer`: перевод hex и именованных цветов в `RgbColor` и обратно.
- `LegacyManipulator`, `PerceptualManipulator`, `SrgbManipulator`: изменение, смешивание и преобразование цветов на разных уровнях абстракции.

## Использование

### Создание объектов цвета

```php
use Bugo\Iris\Spaces\HslColor;
use Bugo\Iris\Spaces\OklchColor;
use Bugo\Iris\Spaces\RgbColor;

$red   = new RgbColor(r: 255.0, g: 0.0, b: 0.0, a: 1.0);
$green = new HslColor(h: 120.0, s: 100.0, l: 50.0, a: 1.0);
$blue  = new OklchColor(l: 45.2, c: 31.3, h: 264.1, a: 1.0);
```

### Конвертация между цветовыми пространствами

```php
use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\Spaces\RgbColor;

$converter = new SpaceConverter();
$rgb = new RgbColor(r: 255.0, g: 128.0, b: 0.0, a: 1.0);

// RGB -> OKLCh объект
$oklch = $converter->rgbToOklch($rgb);
echo $oklch->l; // ~70 по объектной шкале
echo $oklch->h; // ~55

// RGB -> XYZ D65 объект
$xyz = $converter->rgbToXyzD65($rgb);
echo $xyz->x;

// Каналы HSL -> каналы RGB (возвращает [r, g, b] как нормализованные float)
[$r, $g, $b] = $converter->hslToRgb(30.0, 1.0, 0.5);

// Нормализованный channel API -> XYZ D65
$xyzFromChannels = $converter->srgbChannelsToXyzD65(1.0, 0.5, 0.0);
```

Методы вида `*Channels*` образуют нормализованный канальный API. Методы, принимающие объекты вроде `RgbColor` или `OklchColor`, остаются объектными точками входа.

### Маршрутизация по имени пространства

```php
use Bugo\Iris\Exceptions\UnsupportedColorSpace;
use Bugo\Iris\SpaceRouter;

$router = new SpaceRouter();

try {
    $rgba = $router->convertToRgba('display-p3', 1.0, 0.5, 0.0, 1.0);
    echo $rgba->r; // нормализованное значение 0-1
} catch (UnsupportedColorSpace $e) {
    // неизвестное цветовое пространство
}

$xyz = $router->convertToXyzD65('rec2020', 0.4, 0.3, 0.2);
echo $xyz->y;
```

### Манипуляции с цветом

```php
use Bugo\Iris\Manipulators\LegacyManipulator;
use Bugo\Iris\Spaces\RgbColor;

$manipulator = new LegacyManipulator();
$color = new RgbColor(r: 200.0, g: 100.0, b: 50.0, a: 1.0);

$gray      = $manipulator->grayscale($color);
$mixed     = $manipulator->mix($color, new RgbColor(0.0, 150.0, 255.0, 1.0), 0.5);
$darker    = $manipulator->darken($color, 10.0);
$saturated = $manipulator->saturate($color, 20.0);
$rotated   = $manipulator->spin($color, 30.0);
```

### Гамут-маппинг

`GamutMapper` возвращает выходящие за пределы sRGB-гамута цвета обратно в гамут двумя алгоритмами из CSS Color Level 4.

```php
use Bugo\Iris\Operations\GamutMapper;
use Bugo\Iris\Spaces\OklchColor;

$mapper = new GamutMapper();
$oklch = new OklchColor(l: 70.0, c: 40.0, h: 30.0, a: 1.0);

$clipped = $mapper->clip($oklch);
$mapped = $mapper->localMinde($oklch);
```

Оба метода принимают и возвращают `OklchColor`. Для других пространств сначала конвертируйте цвет в `OklchColor`.

### Интерполяция CSS `color-mix()`

`ColorMixResolver` реализует правила интерполяции CSS Color Level 4, включая обработку `none`-каналов и все четыре метода интерполяции оттенка.

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

Если у одной стороны канал равен `null`, побеждает значение другой стороны. Если `null` у обеих сторон, `null` остается в результате.

### Hex-кодирование

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

### Парсинг CSS-цветовых литералов

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

### `Serializer` и `CssSerializer`

Используйте `Serializer`, когда вход уже является CSS-строкой и нужна нормализация или опциональная конвертация в hex.

```php
use Bugo\Iris\Serializers\Serializer;

$serializer = new Serializer();

echo $serializer->serialize('#AABBCC', false);           // '#abc'
echo $serializer->serialize('rgb(255, 128, 0)', true);  // '#ff8000'
echo $serializer->serialize('rgb(255, 128, 0)', false); // 'rgb(255, 128, 0)'
```

Используйте `CssSerializer`, когда входом является типизированный объект цвета и нужна CSS-функция.

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
echo $serializer->toCss($oklch, true); // все равно возвращает CSS-строку цвета

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

Если нужен именно hex из `RgbColor`, используйте `CssSerializer::toHex()` или `LiteralSerializer`.

### Конвертация моделей

```php
use Bugo\Iris\Converters\ModelConverter;
use Bugo\Iris\Spaces\RgbColor;

$converter = new ModelConverter();
$rgb = new RgbColor(r: 255.0, g: 128.0, b: 0.0, a: 1.0);
$hsl = $converter->rgbToHslColor($rgb);
$rgbBack = $converter->hslToRgbColor($hsl);
```

### Перцептуальные манипуляции

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

### Манипуляции с линейным RGB

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

### Широкогамутные цветовые пространства

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

### Утилиты полярной математики

```php
use Bugo\Iris\Operations\PolarMath;

$math = new PolarMath();
[$a, $b] = $math->toCartesian(chroma: 0.2, hue: 55.0);
$radians = $math->toRadians(180.0); // pi
```

### Именованные цвета

```php
use Bugo\Iris\NamedColors;

$tomatoRgb = NamedColors::NAMED_RGB['tomato']; // [255.0, 99.0, 71.0]
$redRgb = NamedColors::NAMED_RGB['red'];       // [255.0, 0.0, 0.0]

$hex = NamedColors::toHex('tomato');      // '#ff6347'
$hex = NamedColors::toHex('transparent'); // '#00000000'

NamedColors::isNamedColor('tomato'); // true
$names = NamedColors::getNames();
```

`NamedColors::NAMED_RGB` хранит байтовые значения каналов, а не нормализованные `0-1` числа с плавающей точкой.

### ColorValueInterface

Все классы из `Spaces/*` реализуют `Bugo\Iris\Contracts\ColorValueInterface`.

```php
use Bugo\Iris\Contracts\ColorValueInterface;
use Bugo\Iris\Spaces\OklchColor;

function describeColor(ColorValueInterface $color): string
{
    return sprintf(
        'Пространство: %s, каналы: [%s], альфа: %s',
        $color->getSpace(),
        implode(', ', $color->getChannels()),
        $color->getAlpha(),
    );
}

echo describeColor(new OklchColor(l: 70.0, c: 15.0, h: 55.0, a: 1.0));
```

## Исключения

```php
use Bugo\Iris\Exceptions\IrisException;
use Bugo\Iris\Exceptions\InvalidColorChannel;
use Bugo\Iris\Exceptions\InvalidColorFormat;
use Bugo\Iris\Exceptions\UnsupportedColorSpace;
```

Все исключения наследуют `IrisException`, который наследует `\RuntimeException`.

- `UnsupportedColorSpace`: неизвестное пространство в `SpaceRouter` или других строковых точках входа.
- `InvalidColorFormat`: некорректный CSS-цвет, неподдерживаемый синтаксис функции или неверный вход сериализации.
- `InvalidColorChannel`: некорректные значения каналов в API, которые валидируют содержимое каналов.

## Сравнение с другими реализациями

Смотрите файл [comparisons_results.md](comparisons_results.md) для просмотра результатов.

## Полезные ссылки

* https://www.w3.org/TR/css-color-4/
