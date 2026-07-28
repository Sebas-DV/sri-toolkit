<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use MTZ\Toolkit\Tests\Support\XMLMaker\SampleXmlPayloads;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Exceptions\SchemaException;
use MTZ\Toolkit\XMLMaker\Validation\SchemaLocator;
use MTZ\Toolkit\XMLMaker\Validation\XsdValidator;
use MTZ\Toolkit\XMLMaker\XMLMaker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class XsdValidatorTest extends TestCase
{
    #[Test]
    public function it_accepts_a_generated_document(): void
    {
        $xml = (new XMLMaker())->generate(SampleXmlPayloads::invoice())->toString();

        $validator = new XsdValidator();

        $this->assertTrue($validator->isValid($xml, XmlDocumentType::Invoice));
        $this->assertSame([], $validator->validate($xml, XmlDocumentType::Invoice));
    }

    #[Test]
    public function it_reports_errors_for_a_structurally_invalid_document(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<factura id="comprobante" version="2.1.0"><infoTributaria/></factura>';

        $validator = new XsdValidator();

        $errors = $validator->validate($xml, XmlDocumentType::Invoice);

        $this->assertNotSame([], $errors);
        $this->assertFalse($validator->isValid($xml, XmlDocumentType::Invoice));
    }

    #[Test]
    public function it_reports_errors_for_malformed_xml(): void
    {
        $validator = new XsdValidator();

        $errors = $validator->validate('<factura>not closed', XmlDocumentType::Invoice);

        $this->assertNotSame([], $errors);
    }

    #[Test]
    public function it_resolves_a_schema_for_every_document_type(): void
    {
        $locator = new SchemaLocator();

        foreach (XmlDocumentType::cases() as $type)
        {
            $this->assertFileExists($locator->schemaPath($type));
        }
    }

    #[Test]
    public function it_throws_when_the_schema_is_missing(): void
    {
        $locator = new SchemaLocator(directory: __DIR__ . '/does-not-exist');

        $this->expectException(SchemaException::class);

        $locator->schemaPath(XmlDocumentType::Invoice);
    }
}
