<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use MTZ\Toolkit\XMLMaker\Builders\CreditNoteXmlBuilder;
use MTZ\Toolkit\XMLMaker\Builders\DebitNoteXmlBuilder;
use MTZ\Toolkit\XMLMaker\Builders\DeliveryGuideXmlBuilder;
use MTZ\Toolkit\XMLMaker\Builders\InvoiceXmlBuilder;
use MTZ\Toolkit\XMLMaker\Builders\PurchaseSettlementXmlBuilder;
use MTZ\Toolkit\XMLMaker\Builders\WithholdingReceiptXmlBuilder;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Factories\XmlDocumentBuilderFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class XmlDocumentBuilderFactoryTest extends TestCase
{
    #[Test]
    public function it_creates_invoice_builder(): void
    {
        $builder = (new XmlDocumentBuilderFactory())->make(XmlDocumentType::Invoice);

        $this->assertInstanceOf(InvoiceXmlBuilder::class, $builder);
    }

    #[Test]
    public function it_creates_every_document_builder(): void
    {
        $factory = new XmlDocumentBuilderFactory();

        $this->assertInstanceOf(PurchaseSettlementXmlBuilder::class, $factory->make(XmlDocumentType::PurchaseSettlement));
        $this->assertInstanceOf(CreditNoteXmlBuilder::class, $factory->make(XmlDocumentType::CreditNote));
        $this->assertInstanceOf(DebitNoteXmlBuilder::class, $factory->make(XmlDocumentType::DebitNote));
        $this->assertInstanceOf(DeliveryGuideXmlBuilder::class, $factory->make(XmlDocumentType::DeliveryGuide));
        $this->assertInstanceOf(WithholdingReceiptXmlBuilder::class, $factory->make(XmlDocumentType::WithholdingReceipt));
    }
}
