<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Services;

use DOMDocument;
use MTZ\Toolkit\Signer\Config\SignerConfig;
use MTZ\Toolkit\Signer\Exceptions\InvalidXmlException;

/**
 * Loads and validates an XML document for signing.
 *
 * Accepts either a raw XML string or an existing DOMDocument, validates
 * that the root element has the expected ID, and rejects documents
 * with extra namespace declarations before signing.
 */
final readonly class XmlDocumentLoader
{
    /**
     * @param SignerConfig $config The signer configuration.
     */
    public function __construct(
        private SignerConfig $config = new SignerConfig(),
    ) {
    }

    /**
     * Load and validate the XML to be signed.
     *
     * @param DOMDocument|string $xml A DOMDocument instance or raw XML string.
     * @return DOMDocument The validated DOM document.
     * @throws InvalidXmlException When the XML is empty, malformed, missing the
     *                             expected root ID, or contains extra namespace declarations.
     */
    public function load(DOMDocument|string $xml): DOMDocument
    {
        if ($xml instanceof DOMDocument)
        {
            $document = $xml;
        } else
        {
            $xml = trim($xml);

            if ($xml === '')
            {
                throw InvalidXmlException::empty();
            }

            $document = new DOMDocument($this->config->xmlVersion, $this->config->encoding);
            $document->preserveWhiteSpace = false;
            $document->formatOutput = false;

            $previous = libxml_use_internal_errors(true);
            $loaded = $document->loadXML($xml);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            if (! $loaded)
            {
                throw InvalidXmlException::cannotLoad();
            }
        }

        $this->validateRoot($document);

        return $document;
    }

    /**
     * Validate that the document's root element meets signing requirements.
     *
     * @param DOMDocument $document The loaded DOM document.
     * @return void
     * @throws InvalidXmlException When the root is missing, lacks the expected ID,
     *                             or contains namespace declarations.
     */
    private function validateRoot(DOMDocument $document): void
    {
        $root = $document->documentElement;

        if ($root === null)
        {
            throw InvalidXmlException::cannotLoad();
        }

        $id = $root->getAttribute('Id') ?: $root->getAttribute('id');

        if ($id !== $this->config->documentReferenceId)
        {
            throw InvalidXmlException::missingDocumentId($this->config->documentReferenceId);
        }

        foreach ($root->attributes as $attribute)
        {
            if (str_starts_with($attribute->nodeName, 'xmlns'))
            {
                throw InvalidXmlException::rootContainsNamespace();
            }
        }
    }
}
