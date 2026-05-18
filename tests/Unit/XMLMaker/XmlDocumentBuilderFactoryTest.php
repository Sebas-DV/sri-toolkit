<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use MTZ\Toolkit\XMLMaker\Builders\InvoiceXmlBuilder;
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
}
