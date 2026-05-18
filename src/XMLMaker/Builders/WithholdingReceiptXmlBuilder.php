<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Builders;

use MTZ\Toolkit\XMLMaker\Contracts\XmlDocumentBuilderInterface;
use MTZ\Toolkit\XMLMaker\Data\GeneratedXml;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Exceptions\UnsupportedDocumentTypeException;

class WithholdingReceiptXmlBuilder extends AbstractXmlDocumentBuilder implements XmlDocumentBuilderInterface
{
    public function build(XmlGenerationData $data): GeneratedXml
    {
        throw UnsupportedDocumentTypeException::make($data->documentType->value);
    }
}
