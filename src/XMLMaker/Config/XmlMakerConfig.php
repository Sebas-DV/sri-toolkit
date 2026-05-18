<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Config;

/**
 * Configuration for XML document generation.
 *
 * Holds defaults for XML version, encoding, formatting, and the document root ID.
 */
final readonly class XmlMakerConfig
{
    /**
     * @param string $xmlVersion The XML declaration version (default '1.0').
     * @param string $encoding The XML document encoding (default 'UTF-8').
     * @param bool $formatOutput Whether to pretty-print the XML output (default false).
     * @param string $documentId The root element id attribute value (default 'comprobante').
     */
    public function __construct(
        public string $xmlVersion = '1.0',
        public string $encoding = 'UTF-8',
        public bool $formatOutput = false,
        public string $documentId = 'comprobante',
    ) {
    }
}
