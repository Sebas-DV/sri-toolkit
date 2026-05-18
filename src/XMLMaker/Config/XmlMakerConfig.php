<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Config;

final readonly class XmlMakerConfig
{
    public function __construct(
        public string $xmlVersion = '1.0',
        public string $encoding = 'UTF-8',
        public bool $formatOutput = false,
        public string $documentId = 'comprobante',
    ) {
    }
}
