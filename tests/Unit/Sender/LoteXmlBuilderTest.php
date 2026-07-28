<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Sender;

use DOMDocument;
use DOMXPath;
use MTZ\Toolkit\Sender\Exceptions\BatchException;
use MTZ\Toolkit\Sender\Support\LoteXmlBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LoteXmlBuilderTest extends TestCase
{
    private const LOTE_KEY = '1305202601179001234500110010010000000251234567817';

    #[Test]
    public function it_builds_a_batch_envelope_with_cdata_vouchers(): void
    {
        $xml = (new LoteXmlBuilder())->build(
            self::LOTE_KEY,
            '1790012345001',
            [
                '<?xml version="1.0" encoding="UTF-8"?><factura id="comprobante">A</factura>',
                '<factura id="comprobante">B</factura>',
            ],
        );

        $document = new DOMDocument();
        $this->assertTrue($document->loadXML($xml));

        $this->assertSame('lote', $document->documentElement?->nodeName);
        $this->assertSame('1.0.0', $document->documentElement->getAttribute('version'));

        $xpath = new DOMXPath($document);
        $this->assertSame(self::LOTE_KEY, $xpath->evaluate('string(/lote/claveAcceso)'));
        $this->assertSame('1790012345001', $xpath->evaluate('string(/lote/ruc)'));
        $this->assertSame(2.0, $xpath->evaluate('count(/lote/comprobantes/comprobante)'));

        $this->assertStringContainsString('<![CDATA[<factura id="comprobante">A</factura>]]>', $xml);
        $this->assertStringNotContainsString('<?xml version="1.0" encoding="UTF-8"?><factura', $xml);
    }

    #[Test]
    public function it_rejects_an_empty_batch(): void
    {
        $this->expectException(BatchException::class);

        (new LoteXmlBuilder())->build(self::LOTE_KEY, '1790012345001', []);
    }

    #[Test]
    public function it_rejects_more_than_fifty_vouchers(): void
    {
        $vouchers = array_fill(0, 51, '<factura/>');

        $this->expectException(BatchException::class);

        (new LoteXmlBuilder())->build(self::LOTE_KEY, '1790012345001', $vouchers);
    }

    #[Test]
    public function it_rejects_a_batch_over_the_size_limit(): void
    {
        $huge = '<factura>' . str_repeat('X', BatchException::MAX_SIZE_BYTES) . '</factura>';

        $this->expectException(BatchException::class);

        (new LoteXmlBuilder())->build(self::LOTE_KEY, '1790012345001', [$huge]);
    }
}
