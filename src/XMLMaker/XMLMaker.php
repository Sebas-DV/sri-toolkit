<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker;

use MTZ\Toolkit\XMLMaker\Config\XmlMakerConfig;
use MTZ\Toolkit\XMLMaker\Data\GeneratedXml;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Factories\XmlDocumentBuilderFactory;

/**
 * Main entry point for generating SRI-compliant XML documents.
 *
 * Orchestrates document builder selection via the factory and delegates
 * XML construction to the appropriate document-type-specific builder.
 */
final readonly class XMLMaker
{
    /**
     * @param XmlDocumentBuilderFactory $builderFactory Factory that resolves the correct builder per document type.
     */
    public function __construct(
        private XmlDocumentBuilderFactory $builderFactory = new XmlDocumentBuilderFactory(
            new XmlMakerConfig(),
        ),
    ) {
    }

    /**
     * Generates an SRI XML document from the provided generation data.
     *
     * @param XmlGenerationData $data The document type, environment, access key, and payload.
     * @return GeneratedXml The fully built XML document wrapper.
     */
    public function generate(XmlGenerationData $data): GeneratedXml
    {
        return $this->builderFactory
            ->make($data->documentType)
            ->build($data);
    }
}
