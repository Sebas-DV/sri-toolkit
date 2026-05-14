<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Services;

use DOMDocument;
use MTZ\Toolkit\Signer\Config\SignerConfig;
use MTZ\Toolkit\Signer\Exceptions\InvalidXmlException;

final readonly class XmlDocumentLoader
{
    public function __construct(
        private SignerConfig $config = new SignerConfig(),
    )
    {
    }

    public function load(DOMDocument|string $xml): DOMDocument
    {
        if ($xml instanceof DOMDocument)
        {
            $document = $xml;
        }
        else
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