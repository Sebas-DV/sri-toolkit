<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Factories;

use MTZ\Toolkit\XMLMaker\Builders\CreditNoteXmlBuilder;
use MTZ\Toolkit\XMLMaker\Builders\DebitNoteXmlBuilder;
use MTZ\Toolkit\XMLMaker\Builders\DeliveryGuideXmlBuilder;
use MTZ\Toolkit\XMLMaker\Builders\InvoiceXmlBuilder;
use MTZ\Toolkit\XMLMaker\Builders\PurchaseSettlementXmlBuilder;
use MTZ\Toolkit\XMLMaker\Builders\WithholdingReceiptXmlBuilder;
use MTZ\Toolkit\XMLMaker\Config\XmlMakerConfig;
use MTZ\Toolkit\XMLMaker\Contracts\XmlDocumentBuilderInterface;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;

final readonly class XmlDocumentBuilderFactory
{
    /**
     * @param XmlMakerConfig $config The shared XML generation configuration.
     */
    public function __construct(
        private XmlMakerConfig $config = new XmlMakerConfig(),
    ) {
    }

    /**
     * Creates an XmlDocumentBuilderInterface instance for the specified document type.
     *
     * @param XmlDocumentType $documentType The document type to build.
     * @return XmlDocumentBuilderInterface The builder capable of producing the requested document.
     */
    public function make(XmlDocumentType $documentType): XmlDocumentBuilderInterface
    {
        return match ($documentType)
        {
            XmlDocumentType::Invoice => new InvoiceXmlBuilder($this->config),
            XmlDocumentType::PurchaseSettlement => new PurchaseSettlementXmlBuilder($this->config),
            XmlDocumentType::CreditNote => new CreditNoteXmlBuilder($this->config),
            XmlDocumentType::DebitNote => new DebitNoteXmlBuilder($this->config),
            XmlDocumentType::DeliveryGuide => new DeliveryGuideXmlBuilder($this->config),
            XmlDocumentType::WithholdingReceipt => new WithholdingReceiptXmlBuilder($this->config)
        };
    }
}
