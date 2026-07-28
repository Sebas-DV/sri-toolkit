<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Config;

final readonly class RidePdfConfig
{
    public function __construct(
        public string $format = 'A4',
        public string $orientation = 'P',
        public float $marginLeft = 12,
        public float $marginRight = 12,
        public float $marginTop = 12,
        public float $marginBottom = 12,
        public string $defaultFont = 'Helvetica',
        public string $tempDir = '',
        public ?string $templatesPath = null,
    ) {
    }
}
