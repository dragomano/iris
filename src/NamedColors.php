<?php

declare(strict_types=1);

namespace Bugo\Iris;

use function array_key_exists;
use function array_keys;
use function sprintf;

final class NamedColors
{
    /** @var array<string, list<float>> */
    public const NAMED_RGB = [
        'aliceblue'            => [240.0, 248.0, 255.0],
        'antiquewhite'         => [250.0, 235.0, 215.0],
        'aqua'                 => [0.0, 255.0, 255.0],
        'aquamarine'           => [127.0, 255.0, 212.0],
        'azure'                => [240.0, 255.0, 255.0],
        'beige'                => [245.0, 245.0, 220.0],
        'bisque'               => [255.0, 228.0, 196.0],
        'black'                => [0.0, 0.0, 0.0],
        'blanchedalmond'       => [255.0, 235.0, 205.0],
        'blue'                 => [0.0, 0.0, 255.0],
        'blueviolet'           => [138.0, 43.0, 226.0],
        'brown'                => [165.0, 42.0, 42.0],
        'burlywood'            => [222.0, 184.0, 135.0],
        'cadetblue'            => [95.0, 158.0, 160.0],
        'chartreuse'           => [127.0, 255.0, 0.0],
        'chocolate'            => [210.0, 105.0, 30.0],
        'coral'                => [255.0, 127.0, 80.0],
        'cornflowerblue'       => [100.0, 149.0, 237.0],
        'cornsilk'             => [255.0, 248.0, 220.0],
        'crimson'              => [220.0, 20.0, 60.0],
        'cyan'                 => [0.0, 255.0, 255.0],
        'darkblue'             => [0.0, 0.0, 139.0],
        'darkcyan'             => [0.0, 139.0, 139.0],
        'darkgoldenrod'        => [184.0, 134.0, 11.0],
        'darkgray'             => [169.0, 169.0, 169.0],
        'darkgreen'            => [0.0, 100.0, 0.0],
        'darkgrey'             => [169.0, 169.0, 169.0],
        'darkkhaki'            => [189.0, 183.0, 107.0],
        'darkmagenta'          => [139.0, 0.0, 139.0],
        'darkolivegreen'       => [85.0, 107.0, 47.0],
        'darkorange'           => [255.0, 140.0, 0.0],
        'darkorchid'           => [153.0, 50.0, 204.0],
        'darkred'              => [139.0, 0.0, 0.0],
        'darksalmon'           => [233.0, 150.0, 122.0],
        'darkseagreen'         => [143.0, 188.0, 143.0],
        'darkslateblue'        => [72.0, 61.0, 139.0],
        'darkslategray'        => [47.0, 79.0, 79.0],
        'darkslategrey'        => [47.0, 79.0, 79.0],
        'darkturquoise'        => [0.0, 206.0, 209.0],
        'darkviolet'           => [148.0, 0.0, 211.0],
        'deeppink'             => [255.0, 20.0, 147.0],
        'deepskyblue'          => [0.0, 191.0, 255.0],
        'dimgray'              => [105.0, 105.0, 105.0],
        'dimgrey'              => [105.0, 105.0, 105.0],
        'dodgerblue'           => [30.0, 144.0, 255.0],
        'firebrick'            => [178.0, 34.0, 34.0],
        'floralwhite'          => [255.0, 250.0, 240.0],
        'forestgreen'          => [34.0, 139.0, 34.0],
        'fuchsia'              => [255.0, 0.0, 255.0],
        'gainsboro'            => [220.0, 220.0, 220.0],
        'ghostwhite'           => [248.0, 248.0, 255.0],
        'gold'                 => [255.0, 215.0, 0.0],
        'goldenrod'            => [218.0, 165.0, 32.0],
        'gray'                 => [128.0, 128.0, 128.0],
        'green'                => [0.0, 128.0, 0.0],
        'greenyellow'          => [173.0, 255.0, 47.0],
        'grey'                 => [128.0, 128.0, 128.0],
        'honeydew'             => [240.0, 255.0, 240.0],
        'hotpink'              => [255.0, 105.0, 180.0],
        'indianred'            => [205.0, 92.0, 92.0],
        'indigo'               => [75.0, 0.0, 130.0],
        'ivory'                => [255.0, 255.0, 240.0],
        'khaki'                => [240.0, 230.0, 140.0],
        'lavender'             => [230.0, 230.0, 250.0],
        'lavenderblush'        => [255.0, 240.0, 245.0],
        'lawngreen'            => [124.0, 252.0, 0.0],
        'lemonchiffon'         => [255.0, 250.0, 205.0],
        'lightblue'            => [173.0, 216.0, 230.0],
        'lightcoral'           => [240.0, 128.0, 128.0],
        'lightcyan'            => [224.0, 255.0, 255.0],
        'lightgoldenrodyellow' => [250.0, 250.0, 210.0],
        'lightgray'            => [211.0, 211.0, 211.0],
        'lightgreen'           => [144.0, 238.0, 144.0],
        'lightgrey'            => [211.0, 211.0, 211.0],
        'lightpink'            => [255.0, 182.0, 193.0],
        'lightsalmon'          => [255.0, 160.0, 122.0],
        'lightseagreen'        => [32.0, 178.0, 170.0],
        'lightskyblue'         => [135.0, 206.0, 250.0],
        'lightslategray'       => [119.0, 136.0, 153.0],
        'lightslategrey'       => [119.0, 136.0, 153.0],
        'lightsteelblue'       => [176.0, 196.0, 222.0],
        'lightyellow'          => [255.0, 255.0, 224.0],
        'lime'                 => [0.0, 255.0, 0.0],
        'limegreen'            => [50.0, 205.0, 50.0],
        'linen'                => [250.0, 240.0, 230.0],
        'magenta'              => [255.0, 0.0, 255.0],
        'maroon'               => [128.0, 0.0, 0.0],
        'mediumaquamarine'     => [102.0, 205.0, 170.0],
        'mediumblue'           => [0.0, 0.0, 205.0],
        'mediumorchid'         => [186.0, 85.0, 211.0],
        'mediumpurple'         => [147.0, 112.0, 219.0],
        'mediumseagreen'       => [60.0, 179.0, 113.0],
        'mediumslateblue'      => [123.0, 104.0, 238.0],
        'mediumspringgreen'    => [0.0, 250.0, 154.0],
        'mediumturquoise'      => [72.0, 209.0, 204.0],
        'mediumvioletred'      => [199.0, 21.0, 133.0],
        'midnightblue'         => [25.0, 25.0, 112.0],
        'mintcream'            => [245.0, 255.0, 250.0],
        'mistyrose'            => [255.0, 228.0, 225.0],
        'moccasin'             => [255.0, 228.0, 181.0],
        'navajowhite'          => [255.0, 222.0, 173.0],
        'navy'                 => [0.0, 0.0, 128.0],
        'oldlace'              => [253.0, 245.0, 230.0],
        'olive'                => [128.0, 128.0, 0.0],
        'olivedrab'            => [107.0, 142.0, 35.0],
        'orange'               => [255.0, 165.0, 0.0],
        'orangered'            => [255.0, 69.0, 0.0],
        'orchid'               => [218.0, 112.0, 214.0],
        'palegoldenrod'        => [238.0, 232.0, 170.0],
        'palegreen'            => [152.0, 251.0, 152.0],
        'paleturquoise'        => [175.0, 238.0, 238.0],
        'palevioletred'        => [219.0, 112.0, 147.0],
        'papayawhip'           => [255.0, 239.0, 213.0],
        'peachpuff'            => [255.0, 218.0, 185.0],
        'peru'                 => [205.0, 133.0, 63.0],
        'pink'                 => [255.0, 192.0, 203.0],
        'plum'                 => [221.0, 160.0, 221.0],
        'powderblue'           => [176.0, 224.0, 230.0],
        'purple'               => [128.0, 0.0, 128.0],
        'rebeccapurple'        => [102.0, 51.0, 153.0],
        'red'                  => [255.0, 0.0, 0.0],
        'rosybrown'            => [188.0, 143.0, 143.0],
        'royalblue'            => [65.0, 105.0, 225.0],
        'saddlebrown'          => [139.0, 69.0, 19.0],
        'salmon'               => [250.0, 128.0, 114.0],
        'sandybrown'           => [244.0, 164.0, 96.0],
        'seagreen'             => [46.0, 139.0, 87.0],
        'seashell'             => [255.0, 245.0, 238.0],
        'sienna'               => [160.0, 82.0, 45.0],
        'silver'               => [192.0, 192.0, 192.0],
        'skyblue'              => [135.0, 206.0, 235.0],
        'slateblue'            => [106.0, 90.0, 205.0],
        'slategray'            => [112.0, 128.0, 144.0],
        'slategrey'            => [112.0, 128.0, 144.0],
        'snow'                 => [255.0, 250.0, 250.0],
        'springgreen'          => [0.0, 255.0, 127.0],
        'steelblue'            => [70.0, 130.0, 180.0],
        'tan'                  => [210.0, 180.0, 140.0],
        'teal'                 => [0.0, 128.0, 128.0],
        'thistle'              => [216.0, 191.0, 216.0],
        'tomato'               => [255.0, 99.0, 71.0],
        'transparent'          => [0.0, 0.0, 0.0, 0.0],
        'turquoise'            => [64.0, 224.0, 208.0],
        'violet'               => [238.0, 130.0, 238.0],
        'wheat'                => [245.0, 222.0, 179.0],
        'white'                => [255.0, 255.0, 255.0],
        'whitesmoke'           => [245.0, 245.0, 245.0],
        'yellow'               => [255.0, 255.0, 0.0],
        'yellowgreen'          => [154.0, 205.0, 50.0],
    ];

    public static function toHex(string $name): ?string
    {
        if (! array_key_exists($name, self::NAMED_RGB)) {
            return null;
        }

        $channels = self::NAMED_RGB[$name];
        $r        = (int) $channels[0];
        $g        = (int) $channels[1];
        $b        = (int) $channels[2];
        $a        = $channels[3] ?? 1.0;

        if ($a < 0.00001) {
            return '#00000000';
        }

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    public static function isNamedColor(string $name): bool
    {
        return array_key_exists($name, self::NAMED_RGB);
    }

    /**
     * @return list<string>
     */
    public static function getNames(): array
    {
        return array_keys(self::NAMED_RGB);
    }
}
