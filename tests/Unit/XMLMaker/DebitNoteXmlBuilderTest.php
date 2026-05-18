<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\XMLMaker\Builders\DebitNoteXmlBuilder;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;
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
        return XmlGenerationData::make(
            documentType: XmlDocumentType::DebitNote,
            environment: XmlEnvironment::Testing,
            accessKey: '1305202605179001234500110010010000000251234567810',
            data: [
                'date' => '13/05/2026',
                'sequential' => '000000025',
                'company' => [
                    'ruc' => '1790012345001',
                    'legal_name' => 'MTZ TEST S.A.',
                    'head_office_address' => 'Quito',
                    'special_taxpayer_number' => '123',
                ],
                'establishment' => [
                    'code' => '001',
                ],
                'emission_point' => [
                    'code' => '001',
                ],
                'customer' => [
                    'identification_type' => '05',
                    'identification_number' => '1710034065',
                    'name' => 'CLIENTE TEST',
                ],
                'referenced_document' => [
                    'document_type' => '01',
                    'number' => '001-001-000000024',
                    'emission_date' => '12/05/2026',
                ],
                'establishment_address' => 'Quito',
                'requires_accounting' => 'NO',
                'total_without_taxes' => '10.00',
                'tax_totals' => [
                    [
                        'code' => '2',
                        'percentage_code' => '4',
                        'rate' => '15.00',
                        'taxable_base' => '10.00',
                        'value' => '1.50',
                    ],
                ],
                'total_amount' => '11.50',
                'payments' => [
                    [
                        'method' => '01',
                        'total' => '11.50',
                    ],
                ],
                'reasons' => [
                    [
                        'reason' => 'Interes por mora',
                        'amount' => '10.00',
                    ],
                ],
            ],
        );
    }
}
