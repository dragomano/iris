<?php

declare(strict_types=1);

namespace Bugo\Iris\Exceptions;

use function sprintf;

final class UnsupportedColorSpace extends IrisException
{
    public function __construct(string $space)
    {
        parent::__construct(sprintf('Unsupported color space: "%s".', $space));
    }
}
