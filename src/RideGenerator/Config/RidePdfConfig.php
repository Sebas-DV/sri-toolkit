<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Config;

final readonly class RidePdfConfig
{
    public function __construct(
        public string $format = 'A4',
        public string $orientation = 'P',
        public float $marginLeft = 8,
        public float $marginRight = 8,
        public float $marginTop = 8,
        public float $marginBottom = 8,
        public string $defaultFont = 'dejavusans',
        public string $tempDir = '',
    ) {
    }
}
