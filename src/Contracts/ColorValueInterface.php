<?php

declare(strict_types=1);

namespace Bugo\Iris\Contracts;

interface ColorValueInterface
{
    public function getSpace(): string;

    /**
     * @return list<float|null>
     */
    public function getChannels(): array;

    public function getAlpha(): float;
}
