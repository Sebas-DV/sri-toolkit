<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Validation;

use DOMDocument;
use LibXMLError;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;

/**
 * Validates generated SRI XML against the bundled official XSD schemas.
 *
 * Intended to run on the unsigned document before signing and sending, catching
 * schema issues offline (SRI reception error 35) instead of at the web service.
 */
final readonly class XsdValidator implements SchemaValidatorInterface
{
    /**
     * @param SchemaLocator $locator Resolves the schema path for each document type.
     */
    public function __construct(
        private SchemaLocator $locator = new SchemaLocator(),
    ) {
    }

    /**
     * Validates an XML string against the schema for the given document type.
     *
     * @param string $xml The XML document to validate.
     * @param XmlDocumentType $type The document type whose schema should be used.
     * @return list<string> The validation error messages; an empty list means the XML is valid.
     */
    public function validate(string $xml, XmlDocumentType $type): array
    {
        $schemaPath = $this->locator->schemaPath($type);

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try
        {
            $document = new DOMDocument();

            if ($document->loadXML($xml) === false)
            {
                return $this->collectErrors();
            }

            if ($document->schemaValidate($schemaPath))
            {
                return [];
            }

            return $this->collectErrors();
        } finally
        {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * Returns whether the XML is valid against the schema for the given document type.
     *
     * @param string $xml The XML document to validate.
     * @param XmlDocumentType $type The document type whose schema should be used.
     * @return bool True when the document validates without errors.
     */
    public function isValid(string $xml, XmlDocumentType $type): bool
    {
        return $this->validate($xml, $type) === [];
    }

    /**
     * Collects the buffered libxml errors into readable strings.
     *
     * @return list<string>
     */
    private function collectErrors(): array
    {
        return array_map(
            static fn (LibXMLError $error): string => sprintf(
                'Line %d: %s',
                $error->line,
                trim($error->message),
            ),
            libxml_get_errors(),
        );
    }
}
