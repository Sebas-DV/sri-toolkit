<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Validation;

use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Exceptions\SchemaException;

/**
 * Resolves the bundled official SRI XSD for a given document type and version.
 *
 * The schemas live in src/XMLMaker/Resources/schemas and are the official SRI
 * files (only their XML 1.1 prolog was normalized to 1.0 so libxml can load
 * them); the W3C xmldsig-core-schema.xsd sits alongside to satisfy the optional
 * ds:Signature import.
 */
final readonly class SchemaLocator
{
    /**
     * @param string|null $directory Optional override for the schema directory.
     */
    public function __construct(
        private ?string $directory = null,
    ) {
    }

    /**
     * Returns the absolute path to the schema directory.
     *
     * @return string
     */
    public function directory(): string
    {
        return $this->directory ?? dirname(__DIR__) . '/Resources/schemas';
    }

    /**
     * Returns the absolute path to the XSD matching the document type and its target version.
     *
     * @param XmlDocumentType $type The document type to resolve a schema for.
     * @return string The absolute path to the XSD file.
     * @throws SchemaException When the schema file is not present.
     */
    public function schemaPath(XmlDocumentType $type): string
    {
        $path = $this->directory() . '/' . $this->fileName($type);

        if (! is_file($path))
        {
            throw SchemaException::missing($path);
        }

        return $path;
    }

    /**
     * Builds the schema file name from the SRI naming convention and target version.
     *
     * @param XmlDocumentType $type The document type.
     * @return string The XSD file name (e.g. 'factura_V2.1.0.xsd').
     */
    private function fileName(XmlDocumentType $type): string
    {
        $prefix = match ($type)
        {
            XmlDocumentType::Invoice => 'factura',
            XmlDocumentType::PurchaseSettlement => 'LiquidacionCompra',
            XmlDocumentType::CreditNote => 'NotaCredito',
            XmlDocumentType::DebitNote => 'NotaDebito',
            XmlDocumentType::DeliveryGuide => 'GuiaRemision',
            XmlDocumentType::WithholdingReceipt => 'ComprobanteRetencion',
        };

        return sprintf('%s_V%s.xsd', $prefix, $type->version());
    }
}
