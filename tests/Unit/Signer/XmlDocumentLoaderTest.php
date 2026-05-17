<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Signer;

use MTZ\Toolkit\Signer\Exceptions\InvalidXmlException;
use MTZ\Toolkit\Signer\Services\XmlDocumentLoader;
use PHPUnit\Framework\Attributes\Test;
use React\Promise\TestCase;
use DOMDocument;

final class XmlDocumentLoaderTest extends TestCase
{
    #[Test]
    public function it_loads_a_valid_xml_document(): void
    {
        $loader = new XmlDocumentLoader();

        $document = $loader->load('<factura id="comprobante"><infoTributaria/></factura>');

        $this->assertInstanceOf(DOMDocument::class, $document);
        $this->assertSame('factura', $document->documentElement?->nodeName);
    }

    #[Test]
    public function it_fails_when_xml_is_empty(): void
    {
        $this->expectException(InvalidXmlException::class);

        (new XmlDocumentLoader())->load('<factura>');
    }

    #[Test]
    public function it_fails_when_root_does_not_have_voucher_id(): void
    {
        $this->expectException(InvalidXmlException::class);

        (new XmlDocumentLoader())->load('<factura><infoTributaria/></factura>');
    }

    public function it_fails_when_root_has_namespaces_before_signing(): void
    {
        $this->expectException(InvalidXmlException::class);

        (new XmlDocumentLoader())->load(
            '<factura id="comprobante" xmlns:ds="http://www.w3.org/2000/09/xmldsig#"><infoTributaria/></factura>'
        );
    }
}