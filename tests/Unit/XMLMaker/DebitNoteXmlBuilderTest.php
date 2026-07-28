<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\Tests\Support\XMLMaker\SampleXmlPayloads;
use MTZ\Toolkit\XMLMaker\Builders\DebitNoteXmlBuilder;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DebitNoteXmlBuilderTest extends TestCase
{
    /**
     * @throws DOMException
     */
    #[Test]
    public function it_builds_a_debit_note_xml(): void
    {
        $xml = (new DebitNoteXmlBuilder())->build($this->debitNoteData())->toString();

        $this->assertStringContainsString('<notaDebito id="comprobante" version="1.0.0">', $xml);
        $this->assertStringContainsString('<codDoc>05</codDoc>', $xml);
        $this->assertStringContainsString('<infoNotaDebito>', $xml);
        $this->assertStringContainsString('<valorTotal>11.50</valorTotal>', $xml);
        $this->assertStringNotContainsString('<importeTotal>', $xml);
        $this->assertStringContainsString('<motivos>', $xml);

        $document = new DOMDocument();

        $this->assertTrue($document->loadXML($xml));
    }

    private function debitNoteData(): XmlGenerationData
    {
        return SampleXmlPayloads::debitNote();
    }
}
