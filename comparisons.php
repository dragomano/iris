<?php

declare(strict_types=1);

/**
 * Color Conversion Comparison Tool
 *
 * Compares color conversion results between:
 * - bugo/iris (our package)
 * - colorjs.io (npm install colorjs.io)
 * - colour-science (pip install --user colour-science)
 *
 * Usage:
 *   php comparisons.php [--only-mismatches] [--format=table|json|markdown]
 */

require_once __DIR__ . '/vendor/autoload.php';

use Bugo\Iris\Converters\SpaceConverter;
use Bugo\Iris\SpaceRouter;
use Bugo\Iris\Spaces\RgbColor;
use Bugo\Iris\Spaces\XyzColor;

const NUMBER_PATTERN = '[+-]?(?:\d+(?:\.\d+)?|\.\d+)';
const HUE_PATTERN = '[+-]?(?:\d+(?:\.\d+)?|\.\d+)(?:deg)?';

$testCases = [
    'srgb-red'           => ['input' => 'color(srgb 1 0 0)', 'space' => 'srgb'],
    'srgb-green'         => ['input' => 'color(srgb 0 1 0)', 'space' => 'srgb'],
    'srgb-blue'          => ['input' => 'color(srgb 0 0 1)', 'space' => 'srgb'],
    'srgb-white'         => ['input' => 'color(srgb 1 1 1)', 'space' => 'srgb'],
    'srgb-black'         => ['input' => 'color(srgb 0 0 0)', 'space' => 'srgb'],
    'srgb-gray'          => ['input' => 'color(srgb 0.5 0.5 0.5)', 'space' => 'srgb'],
    'srgb-linear-red'    => ['input' => 'color(srgb-linear 1 0 0)', 'space' => 'srgb-linear'],
    'srgb-linear-white'  => ['input' => 'color(srgb-linear 1 1 1)', 'space' => 'srgb-linear'],
    'display-p3-red'     => ['input' => 'color(display-p3 1 0 0)', 'space' => 'display-p3'],
    'display-p3-green'   => ['input' => 'color(display-p3 0 1 0)', 'space' => 'display-p3'],
    'display-p3-blue'    => ['input' => 'color(display-p3 0 0 1)', 'space' => 'display-p3'],
    'display-p3-linear'  => ['input' => 'color(display-p3-linear 1 0 0)', 'space' => 'display-p3-linear'],
    'a98-rgb-red'        => ['input' => 'color(a98-rgb 1 0 0)', 'space' => 'a98-rgb'],
    'a98-rgb-green'      => ['input' => 'color(a98-rgb 0 1 0)', 'space' => 'a98-rgb'],
    'prophoto-rgb-red'   => ['input' => 'color(prophoto-rgb 1 0 0)', 'space' => 'prophoto-rgb'],
    'prophoto-rgb-green' => ['input' => 'color(prophoto-rgb 0 1 0)', 'space' => 'prophoto-rgb'],
    'rec2020-red'        => ['input' => 'color(rec2020 1 0 0)', 'space' => 'rec2020'],
    'rec2020-green'      => ['input' => 'color(rec2020 0 1 0)', 'space' => 'rec2020'],
    'rec2020-blue'       => ['input' => 'color(rec2020 0 0 1)', 'space' => 'rec2020'],
    'xyz-d65-white'      => ['input' => 'color(xyz-d65 0.9505 1.0000 1.0890)', 'space' => 'xyz-d65'],
    'xyz-d65-gray'       => ['input' => 'color(xyz-d65 0.2 0.2 0.2)', 'space' => 'xyz-d65'],
    'xyz-d50-white'      => ['input' => 'color(xyz-d50 0.9643 1.0000 0.8251)', 'space' => 'xyz-d50'],
    'lab-white'          => ['input' => 'lab(100% 0 0)', 'space' => 'lab'],
    'lab-red'            => ['input' => 'lab(54.29% 80.8 69.89)', 'space' => 'lab'],
    'lab-green'          => ['input' => 'lab(87.82% -79.27 80.99)', 'space' => 'lab'],
    'lab-blue'           => ['input' => 'lab(29.57% 68.29 -112.03)', 'space' => 'lab'],
    'lch-white'          => ['input' => 'lch(100% 0 0)', 'space' => 'lch'],
    'lch-red'            => ['input' => 'lch(54.29% 106.83 40.85)', 'space' => 'lch'],
    'lch-green'          => ['input' => 'lch(87.82% 113.32 134.28)', 'space' => 'lch'],
    'oklab-white'        => ['input' => 'oklab(100% 0 0)', 'space' => 'oklab'],
    'oklab-red'          => ['input' => 'oklab(62.8% 0.2249 0.1258)', 'space' => 'oklab'],
    'oklab-green'        => ['input' => 'oklab(86.64% -0.2339 0.1795)', 'space' => 'oklab'],
    'oklab-blue'         => ['input' => 'oklab(45.2% -0.0325 -0.3115)', 'space' => 'oklab'],
    'oklch-white'        => ['input' => 'oklch(100% 0 0)', 'space' => 'oklch'],
    'oklch-red'          => ['input' => 'oklch(62.8% 0.2577 29.23)', 'space' => 'oklch'],
    'oklch-green'        => ['input' => 'oklch(86.64% 0.2949 142.49)', 'space' => 'oklch'],
    'oklch-blue'         => ['input' => 'oklch(45.2% 0.3132 264.05)', 'space' => 'oklch'],
    'hsl-red'            => ['input' => 'hsl(0 100% 50%)', 'space' => 'hsl'],
    'hsl-green'          => ['input' => 'hsl(120 100% 50%)', 'space' => 'hsl'],
    'hsl-blue'           => ['input' => 'hsl(240 100% 50%)', 'space' => 'hsl'],
    'hwb-red'            => ['input' => 'hwb(0 0% 0%)', 'space' => 'hwb'],
    'hwb-green'          => ['input' => 'hwb(120 0% 0%)', 'space' => 'hwb'],
];

$targetSpaces = [
    'srgb',
    'srgb-linear',
    'display-p3',
    'display-p3-linear',
    'a98-rgb',
    'prophoto-rgb',
    'rec2020',
    'xyz-d65',
    'xyz-d50',
    'lab',
    'lch',
    'oklab',
    'oklch',
    'hsl',
    'hwb',
];

$options               = parseCliOptions($argv ?? []);
$pythonCommand         = resolvePythonCommand($options);
$pythonColourAvailable = $pythonCommand !== null && isPythonColourAvailable($pythonCommand);
$colorjsAvailable      = is_dir(__DIR__ . '/node_modules/colorjs.io');

$tools = [
    'colorjs'       => $colorjsAvailable,
    'python-colour' => $pythonColourAvailable,
];

$tasks          = buildTasks($testCases, $targetSpaces);
$irisResults    = loadIrisResults($tasks);
$colorjsResults = $colorjsAvailable ? loadColorJsResults($tasks) : [];
$pythonResults  = $pythonColourAvailable ? loadPythonColourResults($tasks, $pythonCommand) : [];

$allRows = [];

foreach ($tasks as $task) {
    $key     = $task['key'];
    $iris    = $irisResults[$key] ?? null;
    $colorjs = $colorjsResults[$key] ?? null;
    $python  = $pythonResults[$key] ?? null;
    $match   = determineMatch($iris, $colorjs, $python, $task['targetSpace']);

    if ($options['onlyMismatches'] && $match === 'both') {
        continue;
    }

    $allRows[] = [
        'Input'          => $task['label'],
        'Source'         => $task['sourceSpace'],
        'Target'         => $task['targetSpace'],
        'bugo/iris'      => formatValues($iris),
        'colorjs.io'     => formatValues($colorjs),
        'colour-science' => formatValues($python),
        'Match'          => $match,
    ];
}

$summary   = summarizeMatches($tasks, $irisResults, $colorjsResults, $pythonResults);
$timestamp = date('Y-m-d H:i:s');

$reportPayload = [
    'generatedAt' => $timestamp,
    'tools'       => $tools,
    'summary'     => $summary,
    'results'     => $allRows,
];

file_put_contents(
    __DIR__ . '/comparisons_results.json',
    json_encode($reportPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
);

$markdownReport = renderMarkdownReport($allRows, $summary, $tools, $timestamp);

file_put_contents(__DIR__ . '/comparisons_results.md', $markdownReport);

echo 'Results exported to comparisons_results.json and comparisons_results.md' . PHP_EOL;

function parseCliOptions(array $argv): array
{
    $options = [
        'onlyMismatches' => false,
        'format'         => 'table',
        'pythonCommand'  => null,
    ];

    foreach (array_slice($argv, 1) as $argument) {
        if ($argument === '--only-mismatches') {
            $options['onlyMismatches'] = true;

            continue;
        }

        if (str_starts_with($argument, '--format=')) {
            $format = substr($argument, 9);

            if (in_array($format, ['table', 'json', 'markdown'], true)) {
                $options['format'] = $format;
            }

            continue;
        }

        if (str_starts_with($argument, '--python-command=')) {
            $pythonCommand = substr($argument, 17);

            if ($pythonCommand !== '') {
                $options['pythonCommand'] = $pythonCommand;
            }
        }
    }

    return $options;
}

function buildTasks(array $testCases, array $targetSpaces): array
{
    $tasks = [];

    foreach ($testCases as $label => $testCase) {
        foreach ($targetSpaces as $targetSpace) {
            $tasks[] = [
                'key'         => taskKey($label, $targetSpace),
                'label'       => $label,
                'input'       => $testCase['input'],
                'sourceSpace' => $testCase['space'],
                'targetSpace' => $targetSpace,
            ];
        }
    }

    return $tasks;
}

function taskKey(string $label, string $targetSpace): string
{
    return $label . '|' . $targetSpace;
}

function findPythonCommand(): ?string
{
    foreach (pythonCommandCandidates() as $command) {
        $result = runCommand($command . ' --version');

        if ($result['exitCode'] === 0) {
            return $command;
        }
    }

    return null;
}

function resolvePythonCommand(array $options): ?string
{
    $cliCommand = $options['pythonCommand'] ?? null;

    if (is_string($cliCommand) && $cliCommand !== '') {
        return $cliCommand;
    }

    $environmentCommand = getenv('IRIS_PYTHON');

    if (is_string($environmentCommand) && $environmentCommand !== '') {
        return $environmentCommand;
    }

    return findPythonCommand();
}

function isPythonColourAvailable(string $pythonCommand): bool
{
    $result = runCommand($pythonCommand . " -c \"import colour; print('ok')\"");

    return $result['exitCode'] === 0 && trim($result['stdout']) === 'ok';
}

function pythonCommandCandidates(): array
{
    return [
        'python',
        'python3',
        'py -3',
        'py',
    ];
}

function runCommand(string $command): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = @proc_open($command, $descriptors, $pipes);

    if (! is_resource($process)) {
        return [
            'stdout'   => '',
            'stderr'   => '',
            'exitCode' => 1,
        ];
    }

    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'stdout'   => is_string($stdout) ? $stdout : '',
        'stderr'   => is_string($stderr) ? $stderr : '',
        'exitCode' => proc_close($process),
    ];
}

function loadIrisResults(array $tasks): array
{
    $results = [];

    foreach ($tasks as $task) {
        $results[$task['key']] = convertWithIris($task['input'], $task['targetSpace']);
    }

    return $results;
}

function convertWithIris(string $inputColor, string $targetSpace): ?array
{
    try {
        $parsed = parseInputColor($inputColor);

        if ($parsed === null) {
            return null;
        }

        $converter = new SpaceConverter();
        $router    = new SpaceRouter($converter);
        $xyz       = convertParsedInputToXyzD65($parsed, $converter, $router);

        if ($xyz === null) {
            return null;
        }

        return convertXyzToTargetSpace($xyz, $parsed['alpha'], $targetSpace, $converter);
    } catch (Throwable) {
        return null;
    }
}

function parseInputColor(string $inputColor): ?array
{
    $trimmed = trim(strtolower($inputColor));

    if (preg_match(
        '/^color\(\s*([a-z0-9-]+)\s+(' . NUMBER_PATTERN . ')\s+(' . NUMBER_PATTERN . ')\s+(' . NUMBER_PATTERN . ')(?:\s*\/\s*(' . NUMBER_PATTERN . '))?\s*\)$/',
        $trimmed,
        $matches
    )) {
        return [
            'space'  => $matches[1],
            'values' => [(float) $matches[2], (float) $matches[3], (float) $matches[4]],
            'alpha'  => isset($matches[5]) ? (float) $matches[5] : 1.0,
        ];
    }

    if (preg_match(
        '/^lab\(\s*(' . NUMBER_PATTERN . ')%?\s+(' . NUMBER_PATTERN . ')\s+(' . NUMBER_PATTERN . ')(?:\s*\/\s*(' . NUMBER_PATTERN . '))?\s*\)$/',
        $trimmed,
        $matches
    )) {
        return [
            'space'  => 'lab',
            'values' => [(float) $matches[1], (float) $matches[2], (float) $matches[3]],
            'alpha'  => isset($matches[4]) ? (float) $matches[4] : 1.0,
        ];
    }

    if (preg_match(
        '/^lch\(\s*(' . NUMBER_PATTERN . ')%?\s+(' . NUMBER_PATTERN . ')\s+(' . HUE_PATTERN . ')(?:\s*\/\s*(' . NUMBER_PATTERN . '))?\s*\)$/',
        $trimmed,
        $matches
    )) {
        return [
            'space'  => 'lch',
            'values' => [(float) $matches[1], (float) $matches[2], parseHue($matches[3])],
            'alpha'  => isset($matches[4]) ? (float) $matches[4] : 1.0,
        ];
    }

    if (preg_match(
        '/^oklab\(\s*(' . NUMBER_PATTERN . ')%?\s+(' . NUMBER_PATTERN . ')\s+(' . NUMBER_PATTERN . ')(?:\s*\/\s*(' . NUMBER_PATTERN . '))?\s*\)$/',
        $trimmed,
        $matches
    )) {
        return [
            'space'  => 'oklab',
            'values' => [(float) $matches[1] / 100.0, (float) $matches[2], (float) $matches[3]],
            'alpha'  => isset($matches[4]) ? (float) $matches[4] : 1.0,
        ];
    }

    if (preg_match(
        '/^oklch\(\s*(' . NUMBER_PATTERN . ')%?\s+(' . NUMBER_PATTERN . ')\s+(' . HUE_PATTERN . ')(?:\s*\/\s*(' . NUMBER_PATTERN . '))?\s*\)$/',
        $trimmed,
        $matches
    )) {
        return [
            'space'  => 'oklch',
            'values' => [(float) $matches[1] / 100.0, (float) $matches[2], parseHue($matches[3])],
            'alpha'  => isset($matches[4]) ? (float) $matches[4] : 1.0,
        ];
    }

    if (preg_match(
        '/^hsl\(\s*(' . HUE_PATTERN . ')\s*[,\s]\s*(' . NUMBER_PATTERN . ')%?\s*[,\s]\s*(' . NUMBER_PATTERN . ')%?(?:\s*\/\s*(' . NUMBER_PATTERN . '))?\s*\)$/',
        $trimmed,
        $matches
    )) {
        return [
            'space'  => 'hsl',
            'values' => [parseHue($matches[1]), (float) $matches[2] / 100.0, (float) $matches[3] / 100.0],
            'alpha'  => isset($matches[4]) ? (float) $matches[4] : 1.0,
        ];
    }

    if (preg_match(
        '/^hwb\(\s*(' . HUE_PATTERN . ')\s+(' . NUMBER_PATTERN . ')%?\s+(' . NUMBER_PATTERN . ')%?(?:\s*\/\s*(' . NUMBER_PATTERN . '))?\s*\)$/',
        $trimmed,
        $matches
    )) {
        return [
            'space'  => 'hwb',
            'values' => [parseHue($matches[1]), (float) $matches[2] / 100.0, (float) $matches[3] / 100.0],
            'alpha'  => isset($matches[4]) ? (float) $matches[4] : 1.0,
        ];
    }

    return null;
}

function parseHue(string $value): float
{
    return (float) str_replace('deg', '', $value);
}

function convertParsedInputToXyzD65(array $parsed, SpaceConverter $converter, SpaceRouter $router): ?XyzColor
{
    [$c1, $c2, $c3] = $parsed['values'];

    return match ($parsed['space']) {
        'srgb',
        'srgb-linear',
        'display-p3',
        'display-p3-linear',
        'a98-rgb',
        'prophoto-rgb',
        'rec2020',
        'xyz',
        'xyz-d65',
        'xyz-d50',
        'lab',
        'lch',
        'oklab',
        'oklch' => $router->convertToXyzD65($parsed['space'], $c1, $c2, $c3),
        'hsl'   => convertRgbToXyz($converter->hslToRgb($c1, $c2, $c3), $converter),
        'hwb'   => convertRgbToXyz($converter->hwbToRgb($c1, $c2, $c3), $converter),
        default => null,
    };
}

function convertRgbToXyz(array $rgbChannels, SpaceConverter $converter): XyzColor
{
    return $converter->srgbChannelsToXyzD65($rgbChannels[0], $rgbChannels[1], $rgbChannels[2]);
}

function convertXyzToTargetSpace(XyzColor $xyz, float $alpha, string $targetSpace, SpaceConverter $converter): ?array
{
    return match ($targetSpace) {
        'srgb' => [
            'type'   => 'srgb',
            'values' => rgbColorToArray($converter->xyzD65ToSrgba($xyz, $alpha)),
        ],
        'srgb-linear' => convertXyzToSrgbLinear($xyz, $alpha, $converter),
        'display-p3'  => [
            'type'   => 'display-p3',
            'values' => [...$converter->xyzD65ToDisplayP3($xyz), $alpha],
        ],
        'display-p3-linear' => [
            'type'   => 'display-p3-linear',
            'values' => [...$converter->xyzD65ToLinearDisplayP3($xyz), $alpha],
        ],
        'a98-rgb' => [
            'type'   => 'a98-rgb',
            'values' => [...$converter->xyzD65ToA98Rgb($xyz), $alpha],
        ],
        'prophoto-rgb' => convertXyzToProphoto($xyz, $alpha, $converter),
        'rec2020'      => [
            'type'   => 'rec2020',
            'values' => [...$converter->xyzD65ToRec2020($xyz), $alpha],
        ],
        'xyz-d65' => [
            'type'   => 'xyz-d65',
            'values' => [$xyz->x, $xyz->y, $xyz->z, $alpha],
        ],
        'xyz-d50' => convertXyzToD50($xyz, $alpha, $converter),
        'lab'     => convertXyzToLab($xyz, $alpha, $converter),
        'lch'     => convertXyzToLch($xyz, $alpha, $converter),
        'oklab'   => [
            'type'   => 'oklab',
            'values' => [...$converter->xyzToOklabD65($xyz), $alpha],
        ],
        'oklch' => convertXyzToOklch($xyz, $alpha, $converter),
        'hsl'   => convertXyzToHsl($xyz, $alpha, $converter),
        'hwb'   => convertXyzToHwb($xyz, $alpha, $converter),
        default => null,
    };
}

function convertXyzToSrgbLinear(XyzColor $xyz, float $alpha, SpaceConverter $converter): array
{
    $rgb = $converter->xyzD65ToSrgba($xyz, $alpha);

    return [
        'type'   => 'srgb-linear',
        'values' => [
            $converter->srgbToLinear($rgb->r),
            $converter->srgbToLinear($rgb->g),
            $converter->srgbToLinear($rgb->b),
            $alpha,
        ],
    ];
}

function convertXyzToProphoto(XyzColor $xyz, float $alpha, SpaceConverter $converter): array
{
    $xyzD50 = $converter->xyzD65ToXyzD50($xyz);

    return [
        'type'   => 'prophoto-rgb',
        'values' => [...$converter->xyzD50ToProphotoRgb($xyzD50), $alpha],
    ];
}

function convertXyzToD50(XyzColor $xyz, float $alpha, SpaceConverter $converter): array
{
    $xyzD50 = $converter->xyzD65ToXyzD50($xyz);

    return [
        'type'   => 'xyz-d50',
        'values' => [$xyzD50->x, $xyzD50->y, $xyzD50->z, $alpha],
    ];
}

function convertXyzToLab(XyzColor $xyz, float $alpha, SpaceConverter $converter): array
{
    $xyzD50 = $converter->xyzD65ToXyzD50($xyz);

    [$l, $a, $b] = $converter->xyzToLabD50($xyzD50);

    return [
        'type'   => 'lab',
        'values' => [$l, $a, $b, $alpha],
    ];
}

function convertXyzToLch(XyzColor $xyz, float $alpha, SpaceConverter $converter): array
{
    $xyzD50 = $converter->xyzD65ToXyzD50($xyz);

    [$l, $c, $h] = $converter->xyzToLchD50($xyzD50);

    if ($c <= 0.000001) {
        $h = 0.0;
    }

    return [
        'type' => 'lch',
        'values' => [$l, $c, normalizeHue($h), $alpha],
    ];
}

function convertXyzToOklch(XyzColor $xyz, float $alpha, SpaceConverter $converter): array
{
    [$l, $c, $h] = $converter->xyzToOklchD65($xyz);

    if ($c <= 0.000001) {
        $h = 0.0;
    }

    return [
        'type'   => 'oklch',
        'values' => [$l, $c, normalizeHue($h), $alpha],
    ];
}

function convertXyzToHsl(XyzColor $xyz, float $alpha, SpaceConverter $converter): array
{
    $rgb = $converter->xyzD65ToSrgba($xyz, $alpha);

    return [
        'type'   => 'hsl',
        'values' => [...convertSrgbToHsl($rgb->r, $rgb->g, $rgb->b), $alpha],
    ];
}

function convertXyzToHwb(XyzColor $xyz, float $alpha, SpaceConverter $converter): array
{
    $rgb = $converter->xyzD65ToSrgba($xyz, $alpha);

    return [
        'type'   => 'hwb',
        'values' => [...convertSrgbToHwb($rgb->r, $rgb->g, $rgb->b), $alpha],
    ];
}

function rgbColorToArray(RgbColor $rgb): array
{
    return [$rgb->r, $rgb->g, $rgb->b, $rgb->a];
}

function convertSrgbToHsl(float $r, float $g, float $b): array
{
    $max       = max($r, $g, $b);
    $min       = min($r, $g, $b);
    $delta     = $max - $min;
    $lightness = ($max + $min) / 2.0;

    if ($delta == 0.0) {
        return [0.0, 0.0, $lightness * 100.0];
    }

    $saturation = $lightness > 0.5
        ? $delta / (2.0 - $max - $min)
        : $delta / ($max + $min);

    if ($max == $r) {
        $hue = 60.0 * fmod((($g - $b) / $delta), 6.0);
    } elseif ($max == $g) {
        $hue = 60.0 * ((($b - $r) / $delta) + 2.0);
    } else {
        $hue = 60.0 * ((($r - $g) / $delta) + 4.0);
    }

    return [normalizeHue($hue), $saturation * 100.0, $lightness * 100.0];
}

function convertSrgbToHwb(float $r, float $g, float $b): array
{
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);

    [$hue] = convertSrgbToHsl($r, $g, $b);

    return [$hue, $min * 100.0, (1.0 - $max) * 100.0];
}

function loadColorJsResults(array $tasks): array
{
    $script = <<<'JS'
    const fs = require('fs');
    const Color = require(process.argv[3]).default;

    const payload = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
    const spaceMap = {
      srgb: 'srgb',
      'srgb-linear': 'srgb-linear',
      'display-p3': 'p3',
      'display-p3-linear': 'p3-linear',
      'a98-rgb': 'a98rgb',
      'prophoto-rgb': 'prophoto',
      rec2020: 'rec2020',
      'xyz-d65': 'xyz-d65',
      'xyz-d50': 'xyz-d50',
      lab: 'lab',
      lch: 'lch',
      oklab: 'oklab',
      oklch: 'oklch',
      hsl: 'hsl',
      hwb: 'hwb',
    };

    function normalizeHue(space, values) {
      if ((space === 'lch' || space === 'oklch') && values[1] <= 1e-6) {
        values[2] = 0;
      }
      if (space === 'hsl' && values[1] <= 1e-6) {
        values[0] = 0;
      }
      if (space === 'hwb' && values[1] + values[2] >= 99.999999) {
        values[0] = 0;
      }
      if (space === 'lch' || space === 'oklch' || space === 'hsl' || space === 'hwb') {
        values[space === 'hsl' || space === 'hwb' ? 0 : 2] = ((values[space === 'hsl' || space === 'hwb' ? 0 : 2] % 360) + 360) % 360;
      }
    }

    const results = {};

    for (const task of payload) {
      const target = spaceMap[task.targetSpace];

      if (!target) {
        results[task.key] = null;
        continue;
      }

      try {
        const converted = new Color(task.input).to(target);
        const values = [...converted.coords, converted.alpha].map((value) => Number(Number(value).toFixed(6)));
        normalizeHue(task.targetSpace, values);
        results[task.key] = { type: task.targetSpace, values };
      } catch (error) {
        results[task.key] = null;
      }
    }

    process.stdout.write(JSON.stringify(results));
    JS;

    $modulePath = __DIR__ . '/node_modules/colorjs.io';

    return loadExternalResults($tasks, 'colorjs', $script, 'node', [$modulePath]);
}

function loadPythonColourResults(array $tasks, string $pythonCommand): array
{
    $script = <<<'PYTHON'
    import json
    import math
    import re
    import sys
    import warnings

    warnings.filterwarnings('ignore', category=UserWarning, module='colour')
    warnings.filterwarnings('ignore', category=RuntimeWarning, module='colour')
    warnings.filterwarnings('ignore', message='.*related API features are not available.*')

    try:
        import colour
        import numpy as np
        from colour.utilities import ColourUsageWarning
    except Exception:
        print(json.dumps({}))
        sys.exit(0)

    warnings.filterwarnings('ignore', category=ColourUsageWarning)
    np.seterr(invalid='ignore')

    NUMBER = r'[+-]?(?:\d+(?:\.\d+)?|\.\d+)'
    HUE = r'[+-]?(?:\d+(?:\.\d+)?|\.\d+)(?:deg)?'
    D50_XY = colour.CCS_ILLUMINANTS['CIE 1931 2 Degree Standard Observer']['D50']
    D65_XY = colour.CCS_ILLUMINANTS['CIE 1931 2 Degree Standard Observer']['D65']
    D50_XYZ = colour.xy_to_XYZ(D50_XY)
    D65_XYZ = colour.xy_to_XYZ(D65_XY)

    SPACE_MAP = {
        'srgb': ('sRGB', False),
        'srgb-linear': ('sRGB', True),
        'display-p3': ('Display P3', False),
        'display-p3-linear': ('Display P3', True),
        'a98-rgb': ('Adobe RGB (1998)', False),
        'prophoto-rgb': ('ProPhoto RGB', False),
        'rec2020': ('ITU-R BT.2020', False),
    }

    def normalize_hue(value):
        return value % 360.0

    def is_finite_number(value):
        return math.isfinite(float(value))

    def sanitize_values(values):
        sanitized = []

        for value in values:
            numeric = float(value)

            if not is_finite_number(numeric):
                return None

            sanitized.append(round(numeric, 6))

        return sanitized

    def cartesian_from_polar(chroma, hue):
        hue_rad = math.radians(hue)
        return chroma * math.cos(hue_rad), chroma * math.sin(hue_rad)

    def hue_to_rgb(p, q, t):
        if t < 0:
            t += 1
        if t > 1:
            t -= 1
        if t < 1 / 6:
            return p + (q - p) * 6 * t
        if t < 1 / 2:
            return q
        if t < 2 / 3:
            return p + (q - p) * (2 / 3 - t) * 6
        return p

    def hsl_to_rgb(hue, saturation, lightness):
        if saturation <= 0:
            return np.array([lightness, lightness, lightness])

        q = lightness * (1 + saturation) if lightness < 0.5 else lightness + saturation - lightness * saturation
        p = 2 * lightness - q

        return np.array([
            hue_to_rgb(p, q, hue / 360.0 + 1 / 3),
            hue_to_rgb(p, q, hue / 360.0),
            hue_to_rgb(p, q, hue / 360.0 - 1 / 3),
        ])

    def hwb_to_rgb(hue, whiteness, blackness):
        total = whiteness + blackness
        if total >= 1:
            gray = 0 if total == 0 else whiteness / total
            return np.array([gray, gray, gray])

        rgb = hsl_to_rgb(hue, 1.0, 0.5)
        factor = 1.0 - whiteness - blackness

        return np.array([
            rgb[0] * factor + whiteness,
            rgb[1] * factor + whiteness,
            rgb[2] * factor + whiteness,
        ])

    def rgb_to_hsl(rgb):
        r, g, b = rgb
        max_c = max(r, g, b)
        min_c = min(r, g, b)
        delta = max_c - min_c
        lightness = (max_c + min_c) / 2.0

        if delta == 0:
            return [0.0, 0.0, lightness * 100.0]

        saturation = delta / (2.0 - max_c - min_c) if lightness > 0.5 else delta / (max_c + min_c)

        if max_c == r:
            hue = 60.0 * (((g - b) / delta) % 6.0)
        elif max_c == g:
            hue = 60.0 * (((b - r) / delta) + 2.0)
        else:
            hue = 60.0 * (((r - g) / delta) + 4.0)

        return [normalize_hue(hue), saturation * 100.0, lightness * 100.0]

    def rgb_to_hwb(rgb):
        hsl = rgb_to_hsl(rgb)
        return [hsl[0], min(rgb) * 100.0, (1.0 - max(rgb)) * 100.0]

    def adapt_xyz(xyz, source_whitepoint_xyz, target_whitepoint_xyz):
        return np.asarray(
            colour.adaptation.chromatic_adaptation_VonKries(
                np.asarray(xyz),
                np.asarray(source_whitepoint_xyz),
                np.asarray(target_whitepoint_xyz),
                transform='Bradford',
            )
        )

    def convert_xyz_to_rgb_space(xyz, space_name, linear_output):
        colorspace = colour.RGB_COLOURSPACES[space_name]
        rgb_linear = colour.XYZ_to_RGB(
            np.asarray(xyz),
            colorspace,
            illuminant=D65_XY,
            apply_cctf_encoding=False,
        )

        if linear_output:
            return np.asarray(rgb_linear)

        return np.asarray(colorspace.cctf_encoding(rgb_linear))

    def convert_rgb_space_to_xyz(values, space_name, linear_input):
        colorspace = colour.RGB_COLOURSPACES[space_name]
        rgb_linear = np.asarray(values) if linear_input else np.asarray(colorspace.cctf_decoding(values))

        return np.asarray(
            colour.RGB_to_XYZ(
                rgb_linear,
                colorspace,
                illuminant=D65_XY,
                apply_cctf_decoding=False,
            )
        )

    def parse_color(value):
        lowered = value.strip().lower()

        match = re.match(r'^color\(\s*([a-z0-9-]+)\s+(' + NUMBER + r')\s+(' + NUMBER + r')\s+(' + NUMBER + r')(?:\s*/\s*(' + NUMBER + r'))?\s*\)$', lowered)
        if match:
            return match.group(1), [float(match.group(2)), float(match.group(3)), float(match.group(4))], float(match.group(5) or 1.0)

        match = re.match(r'^lab\(\s*(' + NUMBER + r')%?\s+(' + NUMBER + r')\s+(' + NUMBER + r')(?:\s*/\s*(' + NUMBER + r'))?\s*\)$', lowered)
        if match:
            return 'lab', [float(match.group(1)), float(match.group(2)), float(match.group(3))], float(match.group(4) or 1.0)

        match = re.match(r'^lch\(\s*(' + NUMBER + r')%?\s+(' + NUMBER + r')\s+(' + HUE + r')(?:\s*/\s*(' + NUMBER + r'))?\s*\)$', lowered)
        if match:
            return 'lch', [float(match.group(1)), float(match.group(2)), float(match.group(3).replace('deg', ''))], float(match.group(4) or 1.0)

        match = re.match(r'^oklab\(\s*(' + NUMBER + r')%?\s+(' + NUMBER + r')\s+(' + NUMBER + r')(?:\s*/\s*(' + NUMBER + r'))?\s*\)$', lowered)
        if match:
            return 'oklab', [float(match.group(1)) / 100.0, float(match.group(2)), float(match.group(3))], float(match.group(4) or 1.0)

        match = re.match(r'^oklch\(\s*(' + NUMBER + r')%?\s+(' + NUMBER + r')\s+(' + HUE + r')(?:\s*/\s*(' + NUMBER + r'))?\s*\)$', lowered)
        if match:
            return 'oklch', [float(match.group(1)) / 100.0, float(match.group(2)), float(match.group(3).replace('deg', ''))], float(match.group(4) or 1.0)

        match = re.match(r'^hsl\(\s*(' + HUE + r')\s*(?:,|\s)\s*(' + NUMBER + r')%?\s*(?:,|\s)\s*(' + NUMBER + r')%?(?:\s*/\s*(' + NUMBER + r'))?\s*\)$', lowered)
        if match:
            return 'hsl', [float(match.group(1).replace('deg', '')), float(match.group(2)) / 100.0, float(match.group(3)) / 100.0], float(match.group(4) or 1.0)

        match = re.match(r'^hwb\(\s*(' + HUE + r')\s+(' + NUMBER + r')%?\s+(' + NUMBER + r')%?(?:\s*/\s*(' + NUMBER + r'))?\s*\)$', lowered)
        if match:
            return 'hwb', [float(match.group(1).replace('deg', '')), float(match.group(2)) / 100.0, float(match.group(3)) / 100.0], float(match.group(4) or 1.0)

        return None

    def parsed_to_xyz(parsed):
        space, values, _alpha = parsed

        if space in SPACE_MAP:
            mapped_space, linear_input = SPACE_MAP[space]
            return convert_rgb_space_to_xyz(values, mapped_space, linear_input)

        if space in ('xyz', 'xyz-d65'):
            return np.asarray(values)

        if space == 'xyz-d50':
            return adapt_xyz(values, D50_XYZ, D65_XYZ)

        if space == 'lab':
            xyz_d50 = colour.Lab_to_XYZ(np.asarray(values), illuminant=D50_XY)
            return adapt_xyz(xyz_d50, D50_XYZ, D65_XYZ)

        if space == 'lch':
            xyz_d50 = colour.Lab_to_XYZ(colour.LCHab_to_Lab(np.asarray(values)), illuminant=D50_XY)
            return adapt_xyz(xyz_d50, D50_XYZ, D65_XYZ)

        if space == 'oklab':
            return np.asarray(colour.Oklab_to_XYZ(np.asarray(values)))

        if space == 'oklch':
            lab_a, lab_b = cartesian_from_polar(values[1], values[2])
            return np.asarray(colour.Oklab_to_XYZ(np.asarray([values[0], lab_a, lab_b])))

        if space == 'hsl':
            return np.asarray(colour.sRGB_to_XYZ(hsl_to_rgb(values[0], values[1], values[2])))

        if space == 'hwb':
            return np.asarray(colour.sRGB_to_XYZ(hwb_to_rgb(values[0], values[1], values[2])))

        return None

    def xyz_to_target(xyz, alpha, target_space):
        if target_space == 'srgb':
            values = list(np.asarray(colour.XYZ_to_sRGB(xyz)))
        elif target_space == 'srgb-linear':
            values = list(np.asarray(colour.RGB_COLOURSPACES['sRGB'].cctf_decoding(colour.XYZ_to_sRGB(xyz))))
        elif target_space in SPACE_MAP:
            mapped_space, linear_output = SPACE_MAP[target_space]
            values = list(convert_xyz_to_rgb_space(xyz, mapped_space, linear_output))
        elif target_space == 'xyz-d65':
            values = list(np.asarray(xyz))
        elif target_space == 'xyz-d50':
            values = list(adapt_xyz(xyz, D65_XYZ, D50_XYZ))
        elif target_space == 'lab':
            xyz_d50 = adapt_xyz(xyz, D65_XYZ, D50_XYZ)
            values = list(np.asarray(colour.XYZ_to_Lab(xyz_d50, illuminant=D50_XY)))
        elif target_space == 'lch':
            xyz_d50 = adapt_xyz(xyz, D65_XYZ, D50_XYZ)
            values = list(np.asarray(colour.Lab_to_LCHab(colour.XYZ_to_Lab(xyz_d50, illuminant=D50_XY))))
        elif target_space == 'oklab':
            values = list(np.asarray(colour.XYZ_to_Oklab(xyz)))
        elif target_space == 'oklch':
            oklab = np.asarray(colour.XYZ_to_Oklab(xyz))
            values = [oklab[0], math.sqrt(oklab[1] ** 2 + oklab[2] ** 2), normalize_hue(math.degrees(math.atan2(oklab[2], oklab[1])))]
        elif target_space == 'hsl':
            values = rgb_to_hsl(np.asarray(colour.XYZ_to_sRGB(xyz)))
        elif target_space == 'hwb':
            values = rgb_to_hwb(np.asarray(colour.XYZ_to_sRGB(xyz)))
        else:
            return None

        if target_space == 'lch' and values[1] <= 1e-6:
            values[2] = 0.0
        if target_space == 'oklch' and values[1] <= 1e-6:
            values[2] = 0.0
        if target_space == 'hsl' and values[1] <= 1e-6:
            values[0] = 0.0
        if target_space == 'hwb' and values[1] + values[2] >= 99.999999:
            values[0] = 0.0

        rounded = sanitize_values(values)
        alpha_value = sanitize_values([alpha])

        if rounded is None or alpha_value is None:
            return None

        rounded.append(alpha_value[0])

        return {'type': target_space, 'values': rounded}

    with open(sys.argv[1], 'r', encoding='utf8') as handle:
        tasks = json.load(handle)

    results = {}

    for task in tasks:
        try:
            parsed = parse_color(task['input'])
            if parsed is None:
                results[task['key']] = None
                continue

            xyz = parsed_to_xyz(parsed)
            if xyz is None:
                results[task['key']] = None
                continue

            converted = xyz_to_target(xyz, parsed[2], task['targetSpace'])
            results[task['key']] = converted
        except Exception:
            results[task['key']] = None

    print(json.dumps(results))
    PYTHON;

    return loadExternalResults($tasks, 'python-colour', $script, $pythonCommand);
}

function loadExternalResults(array $tasks, string $prefix, string $script, string $command, array $extraArguments = []): array
{
    $payloadFile = tempnam(sys_get_temp_dir(), $prefix . '-payload-');
    $scriptFile  = tempnam(sys_get_temp_dir(), $prefix . '-script-');

    if ($payloadFile === false || $scriptFile === false) {
        if ($payloadFile !== false) {
            @unlink($payloadFile);
        }
        if ($scriptFile !== false) {
            @unlink($scriptFile);
        }

        return [];
    }

    $extension  = $command === 'node' ? '.cjs' : '.py';
    $scriptPath = $scriptFile . $extension;

    if (! @rename($scriptFile, $scriptPath)) {
        @unlink($scriptFile);
        @unlink($payloadFile);

        return [];
    }

    try {
        file_put_contents($payloadFile, json_encode($tasks, JSON_THROW_ON_ERROR));
        file_put_contents($scriptPath, $script);

        $output = shell_exec(buildCommandLine($command, $scriptPath, $payloadFile, ...$extraArguments));
    } catch (Throwable) {
        $output = null;
    } finally {
        @unlink($scriptPath);
        @unlink($payloadFile);
    }

    if (! is_string($output) || trim($output) === '') {
        return [];
    }

    try {
        $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return [];
    }

    if (! is_array($decoded)) {
        return [];
    }

    return array_map(function ($value) {
        return normalizeExternalResult($value);
    }, $decoded);
}

function buildCommandLine(string $command, string ...$arguments): string
{
    $escapedArguments = array_map(
        static fn(string $argument): string => escapeshellarg($argument),
        $arguments
    );

    return trim($command . ' ' . implode(' ', $escapedArguments));
}

function normalizeExternalResult(mixed $value): ?array
{
    if (! is_array($value)) {
        return null;
    }

    $type   = $value['type'] ?? null;
    $values = $value['values'] ?? null;

    if (! is_string($type) || ! is_array($values)) {
        return null;
    }

    $normalizedValues = [];

    foreach ($values as $component) {
        if (! is_int($component) && ! is_float($component)) {
            return null;
        }

        $normalizedValues[] = (float) $component;
    }

    return [
        'type'   => $type,
        'values' => $normalizedValues,
    ];
}

function determineMatch(?array $iris, ?array $colorjs, ?array $python, string $targetSpace): string
{
    $nodeMatch   = compareResultSets($iris, $colorjs, $targetSpace);
    $pythonMatch = compareResultSets($iris, $python, $targetSpace);

    if ($nodeMatch === 'yes' && $pythonMatch === 'yes') {
        return 'both';
    }

    if ($nodeMatch === 'yes') {
        return 'colorjs';
    }

    if ($pythonMatch === 'yes') {
        return 'python';
    }

    if ($nodeMatch === 'n/a' && $pythonMatch === 'n/a') {
        return 'n/a';
    }

    return 'none';
}

function compareResultSets(?array $left, ?array $right, string $space): string
{
    if ($left === null || $right === null) {
        return 'n/a';
    }

    $leftValues  = $left['values'] ?? null;
    $rightValues = $right['values'] ?? null;

    if (! is_array($leftValues) || ! is_array($rightValues) || count($leftValues) !== count($rightValues)) {
        return 'n/a';
    }

    foreach ($leftValues as $index => $leftValue) {
        $rightValue = $rightValues[$index] ?? null;

        if (! is_float($leftValue) && ! is_int($leftValue)) {
            return 'n/a';
        }

        if (! is_float($rightValue) && ! is_int($rightValue)) {
            return 'n/a';
        }

        $tolerance = componentTolerance($space, $index);

        if (isHueComponent($space, $index)) {
            if (isHueIgnorable($space, $leftValues, $rightValues)) {
                continue;
            }

            if (angularDistance((float) $leftValue, (float) $rightValue) > $tolerance) {
                return 'no';
            }

            continue;
        }

        if (abs((float) $leftValue - (float) $rightValue) > $tolerance) {
            return 'no';
        }
    }

    return 'yes';
}

function componentTolerance(string $space, int $index): float
{
    if ($index === 3) {
        return 0.0001;
    }

    return match ($space) {
        'xyz-d65',
        'xyz-d50',
        'oklab' => 0.0001,
        'lab'   => 0.01,
        'lch'   => $index === 2 ? 0.5 : 0.01,
        'oklch' => $index === 2 ? 0.5 : 0.0001,
        'hsl',
        'hwb'   => 0.5,
        default => 0.005,
    };
}

function isHueComponent(string $space, int $index): bool
{
    return match ($space) {
        'hsl',
        'hwb'   => $index === 0,
        'lch',
        'oklch' => $index === 2,
        default => false,
    };
}

function isHueIgnorable(string $space, array $leftValues, array $rightValues): bool
{
    return match ($space) {
        'lch',
        'oklch',
        'hsl'   => max((float) $leftValues[1], (float) $rightValues[1]) <= componentTolerance($space, 1),
        'hwb'   => ((float) $leftValues[1] + (float) $leftValues[2] >= 99.5)
            && ((float) $rightValues[1] + (float) $rightValues[2] >= 99.5),
        default => false,
    };
}

function angularDistance(float $leftHue, float $rightHue): float
{
    $distance = abs(normalizeHue($leftHue) - normalizeHue($rightHue));

    return min($distance, 360.0 - $distance);
}

function normalizeHue(float $hue): float
{
    $normalized = fmod($hue, 360.0);

    if ($normalized < 0.0) {
        $normalized += 360.0;
    }

    if (abs($normalized) <= 0.000001 || abs($normalized - 360.0) <= 0.000001) {
        return 0.0;
    }

    return $normalized;
}

function summarizeMatches(array $tasks, array $irisResults, array $colorjsResults, array $pythonResults): array
{
    $summary = [
        'total'   => count($tasks),
        'both'    => 0,
        'colorjs' => 0,
        'python'  => 0,
        'none'    => 0,
        'n/a'     => 0,
    ];

    foreach ($tasks as $task) {
        $match = determineMatch(
            $irisResults[$task['key']] ?? null,
            $colorjsResults[$task['key']] ?? null,
            $pythonResults[$task['key']] ?? null,
            $task['targetSpace']
        );

        $summary[$match]++;
    }

    return $summary;
}

function formatValues(?array $data): string
{
    if ($data === null) {
        return 'n/a';
    }

    $values = $data['values'] ?? null;
    $type   = $data['type'] ?? null;

    if (! is_array($values) || ! is_string($type)) {
        return 'n/a';
    }

    $formatted = array_map(
        static fn(float $value): string => number_format($value, 6, '.', ''),
        array_map(static fn(mixed $value): float => (float) $value, $values)
    );

    return $type . '(' . implode(', ', $formatted) . ')';
}

function formatValuesWithSwatch(?array $data): string
{
    $formatted = formatValues($data);

    if ($formatted === 'n/a' || $data === null) {
        return $formatted;
    }

    $swatch = buildColorSwatch($data);

    return $swatch === null ? $formatted : $formatted . ' ' . $swatch;
}

function buildColorSwatch(array $data): ?string
{
    $values = $data['values'] ?? null;
    $type   = $data['type'] ?? null;

    if (! is_array($values) || ! is_string($type) || count($values) < 3) {
        return null;
    }

    try {
        $router = new SpaceRouter();
        $alpha  = isset($values[3]) ? (float) $values[3] : 1.0;
        $rgba   = match ($type) {
            'hsl'   => channelsToRgbColor(convertHslValuesToSrgb((float) $values[0], (float) $values[1], (float) $values[2]), $alpha),
            'hwb'   => channelsToRgbColor(convertHwbValuesToSrgb((float) $values[0], (float) $values[1], (float) $values[2]), $alpha),
            default => $router->convertToRgba($type, (float) $values[0], (float) $values[1], (float) $values[2], $alpha),
        };

        $r = max(0, min(255, (int) round($rgba->r * 255)));
        $g = max(0, min(255, (int) round($rgba->g * 255)));
        $b = max(0, min(255, (int) round($rgba->b * 255)));
        $hex = sprintf('#%02x%02x%02x', $r, $g, $b);

        return '<span style="display:inline-block;width:0.85em;height:0.85em;background:' . $hex . ';border:1px solid #999;vertical-align:middle" title="' . $hex . '"></span>';
    } catch (Throwable) {
        return null;
    }
}

function channelsToRgbColor(array $channels, float $alpha): RgbColor
{
    return new RgbColor($channels[0], $channels[1], $channels[2], $alpha);
}

function convertHslValuesToSrgb(float $hue, float $saturationPercent, float $lightnessPercent): array
{
    $converter = new SpaceConverter();

    return $converter->hslToRgb($hue, $saturationPercent / 100.0, $lightnessPercent / 100.0);
}

function convertHwbValuesToSrgb(float $hue, float $whitenessPercent, float $blacknessPercent): array
{
    $converter = new SpaceConverter();

    return $converter->hwbToRgb($hue, $whitenessPercent / 100.0, $blacknessPercent / 100.0);
}

function renderMarkdownReport(array $rows, array $summary, array $tools, string $timestamp): string
{
    $lines = [];
    $lines[] = '# Color Conversion Comparison Report';
    $lines[] = '';
    $lines[] = '**Generated:** ' . $timestamp;
    $lines[] = '';
    $lines[] = '## Tools';
    $lines[] = '';
    $lines[] = '| Tool | Status |';
    $lines[] = '|------|--------|';
    $lines[] = '| bugo/iris | ✓ |';
    $lines[] = '| colorjs.io | ' . ($tools['colorjs'] ? '✓' : '✗') . ' |';
    $lines[] = '| colour-science | ' . ($tools['python-colour'] ? '✓' : '✗') . ' |';
    $lines[] = '';
    $lines[] = '## Summary';
    $lines[] = '';
    $lines[] = '- **Total comparisons:** ' . $summary['total'];
    $lines[] = '- **Matched both:** ' . $summary['both'];
    $lines[] = '- **Matched colorjs.io only:** ' . $summary['colorjs'];
    $lines[] = '- **Matched colour-science only:** ' . $summary['python'];
    $lines[] = '- **Matched neither:** ' . $summary['none'];
    $lines[] = '- **Unavailable:** ' . $summary['n/a'];
    $lines[] = '';
    $lines[] = '## Results';
    $lines[] = '';
    $lines[] = '| Input | Source | Target | bugo/iris | colorjs.io | colour-science | Match |';
    $lines[] = '|-------|--------|--------|-----------|------------|----------------|-------|';

    foreach ($rows as $row) {
        $lines[] = '| '
            . $row['Input'] . ' | '
            . $row['Source'] . ' | '
            . $row['Target'] . ' | '
            . escapeMarkdownCell(formatStringWithSwatch($row['bugo/iris'])) . ' | '
            . escapeMarkdownCell(formatStringWithSwatch($row['colorjs.io'])) . ' | '
            . escapeMarkdownCell(formatStringWithSwatch($row['colour-science'])) . ' | '
            . $row['Match'] . ' |';
    }

    $lines[] = '';
    $lines[] = '*Report generated by `comparisons.php`*';

    return implode(PHP_EOL, $lines) . PHP_EOL;
}

function escapeMarkdownCell(string $value): string
{
    return str_replace('|', '\|', $value);
}

function formatStringWithSwatch(string $formattedValue): string
{
    $parsed = parseFormattedColor($formattedValue);

    return $parsed === null ? $formattedValue : formatValuesWithSwatch($parsed);
}

function parseFormattedColor(string $formattedValue): ?array
{
    if ($formattedValue === 'n/a') {
        return null;
    }

    $openingParenthesis = strpos($formattedValue, '(');
    $closingParenthesis = strrpos($formattedValue, ')');

    if ($openingParenthesis === false || $closingParenthesis === false || $closingParenthesis <= $openingParenthesis) {
        return null;
    }

    $type         = substr($formattedValue, 0, $openingParenthesis);
    $valuesString = substr($formattedValue, $openingParenthesis + 1, $closingParenthesis - $openingParenthesis - 1);
    $parts        = array_map('trim', explode(',', $valuesString));

    if ($type === '' || $parts === []) {
        return null;
    }

    $values = [];

    foreach ($parts as $part) {
        if ($part === '' || ! is_numeric($part)) {
            return null;
        }

        $values[] = (float) $part;
    }

    return [
        'type'   => $type,
        'values' => $values,
    ];
}
