<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker;

use MTZ\Toolkit\XMLMaker\Config\XmlMakerConfig;
use MTZ\Toolkit\XMLMaker\Data\GeneratedXml;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Factories\XmlDocumentBuilderFactory;

final readonly class XMLMaker
{
    public function __construct(
        private XmlDocumentBuilderFactory $builderFactory = new XmlDocumentBuilderFactory(
            new XmlMakerConfig(),
        ),
    ) {
    }

    public function generate(XmlGenerationData $data): GeneratedXml
    {
        return $this->builderFactory
            ->make($data->documentType)
            ->build($data);
    }
}
