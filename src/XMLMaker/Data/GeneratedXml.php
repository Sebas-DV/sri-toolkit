<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Data;

use DOMDocument;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;

final readonly class GeneratedXml
{
    public function __construct(
        public XmlDocumentType $documentType,
        public string $accessKey,
        public DOMDocument $document,
    ) {
    }

    public function toString(): string
    {
        return $this->document->saveXML() ?: '';
    }
}
