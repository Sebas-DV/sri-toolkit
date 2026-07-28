<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use MTZ\Toolkit\XMLMaker\Calculation\TotalsCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TotalsCalculatorTest extends TestCase
{
    #[Test]
    public function it_aggregates_taxes_by_code_and_percentage(): void
    {
        $details = [
            [
                'total_without_tax' => '100.00',
                'taxes' => [
                    ['code' => '2', 'percentage_code' => '5', 'rate' => '5.00', 'taxable_base' => '100.00', 'value' => '5.00'],
                ],
            ],
            [
                'total_without_tax' => '180.00',
                'taxes' => [
                    ['code' => '2', 'percentage_code' => '4', 'rate' => '15.00', 'taxable_base' => '180.00', 'value' => '27.00'],
                ],
            ],
            [
                'total_without_tax' => '50.00',
                'taxes' => [
                    ['code' => '2', 'percentage_code' => '4', 'rate' => '15.00', 'taxable_base' => '50.00', 'value' => '7.50'],
                ],
            ],
        ];

        $totals = (new TotalsCalculator())->calculate($details);

        $this->assertSame('330.00', $totals->totalWithoutTaxes);
        $this->assertSame('369.50', $totals->importeTotal);
        $this->assertSame('0.00', $totals->ice);
        $this->assertSame('0.00', $totals->irbpnr);

        $this->assertCount(2, $totals->taxTotals);

        $this->assertSame('2', $totals->taxTotals[0]->code);
        $this->assertSame('5', $totals->taxTotals[0]->percentageCode);
        $this->assertSame('100.00', $totals->taxTotals[0]->taxableBase);
        $this->assertSame('5.00', $totals->taxTotals[0]->value);

        $this->assertSame('4', $totals->taxTotals[1]->percentageCode);
        $this->assertSame('230.00', $totals->taxTotals[1]->taxableBase);
        $this->assertSame('34.50', $totals->taxTotals[1]->value);
    }

    #[Test]
    public function it_sums_discounts_ice_and_tip(): void
    {
        $details = [
            [
                'total_without_tax' => '100.00',
                'discount' => '5.00',
                'taxes' => [
                    ['code' => '2', 'percentage_code' => '4', 'rate' => '15.00', 'taxable_base' => '100.00', 'value' => '15.00'],
                    ['code' => '3', 'percentage_code' => '3011', 'taxable_base' => '100.00', 'value' => '10.00'],
                ],
            ],
        ];

        $totals = (new TotalsCalculator())->calculate($details, tip: 2.00);

        $this->assertSame('100.00', $totals->totalWithoutTaxes);
        $this->assertSame('5.00', $totals->totalDiscount);
        $this->assertSame('10.00', $totals->ice);
        // 100 + (15 IVA + 10 ICE) + 2 tip
        $this->assertSame('127.00', $totals->importeTotal);
        $this->assertCount(2, $totals->taxTotals);
    }

    #[Test]
    public function it_exposes_a_builder_ready_payload(): void
    {
        $details = [
            [
                'total_without_tax' => '10.00',
                'taxes' => [
                    ['code' => '2', 'percentage_code' => '4', 'rate' => '15.00', 'taxable_base' => '10.00', 'value' => '1.50'],
                ],
            ],
        ];

        $payload = (new TotalsCalculator())->calculate($details)->toArray();

        $this->assertSame('10.00', $payload['total_without_taxes']);
        $this->assertSame('11.50', $payload['total_amount']);
        $this->assertSame(
            [['code' => '2', 'percentage_code' => '4', 'taxable_base' => '10.00', 'value' => '1.50', 'rate' => '15.00']],
            $payload['tax_totals'],
        );
    }

    #[Test]
    public function it_aggregates_and_preserves_the_vat_refund_value(): void
    {
        $details = [
            [
                'total_without_tax' => '50.00',
                'taxes' => [
                    ['code' => '2', 'percentage_code' => '0', 'rate' => '12.00', 'taxable_base' => '50.00', 'value' => '6.00', 'refund_value' => '6.00'],
                ],
            ],
            [
                'total_without_tax' => '50.00',
                'taxes' => [
                    ['code' => '2', 'percentage_code' => '0', 'rate' => '12.00', 'taxable_base' => '50.00', 'value' => '6.00', 'refund_value' => '4.00'],
                ],
            ],
        ];

        $payload = (new TotalsCalculator())->calculate($details)->toArray();

        $this->assertCount(1, $payload['tax_totals']);
        $this->assertSame('10.00', $payload['tax_totals'][0]['refund_value']);
    }

    #[Test]
    public function it_omits_the_refund_value_when_absent(): void
    {
        $details = [
            [
                'total_without_tax' => '10.00',
                'taxes' => [
                    ['code' => '2', 'percentage_code' => '4', 'rate' => '15.00', 'taxable_base' => '10.00', 'value' => '1.50'],
                ],
            ],
        ];

        $payload = (new TotalsCalculator())->calculate($details)->toArray();

        $this->assertArrayNotHasKey('refund_value', $payload['tax_totals'][0]);
    }

    #[Test]
    public function it_handles_empty_details(): void
    {
        $totals = (new TotalsCalculator())->calculate([]);

        $this->assertSame('0.00', $totals->totalWithoutTaxes);
        $this->assertSame('0.00', $totals->importeTotal);
        $this->assertSame([], $totals->taxTotals);
    }
}
