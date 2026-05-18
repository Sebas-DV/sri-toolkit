<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Data;

use DOMDocument;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;

/**
 * Immutable wrapper for a generated SRI XML document.
 *
 * Holds the document type, the computed access key, and the DOMDocument instance.
 */
final readonly class GeneratedXml
{
    /**
     * @param XmlDocumentType $documentType The type of SRI electronic document.
     * @param string $accessKey The SRI access key for the document.
     * @param DOMDocument $document The constructed XML DOM document.
     */
    public function __construct(
        public XmlDocumentType $documentType,
        public string $accessKey,
        public DOMDocument $document,
    ) {
    }

    /**
     * Serializes the DOM document to an XML string.
     *
     * @return string The XML string representation, or an empty string on failure.
     */
    public function toString(): string
    {
        return $this->document->saveXML() ?: '';
    }
}
