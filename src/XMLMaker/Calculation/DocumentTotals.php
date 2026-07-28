<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Calculation;

/**
 * Immutable, internally-consistent set of document totals derived from line items.
 *
 * Emitting these values guarantees the XML totals agree with the detail lines,
 * preventing SRI authorization error 52 (calculation differences).
 */
final readonly class DocumentTotals
{
    /**
     * @param string $totalWithoutTaxes Sum of line net amounts (totalSinImpuestos).
     * @param string $totalDiscount Sum of line discounts.
     * @param string $ice Total ICE tax.
     * @param string $irbpnr Total IRBPNR tax.
     * @param string $importeTotal Grand total (importeTotal).
     * @param list<TaxTotal> $taxTotals Aggregated tax lines (totalConImpuestos).
     */
    public function __construct(
        public string $totalWithoutTaxes,
        public string $totalDiscount,
        public string $ice,
        public string $irbpnr,
        public string $importeTotal,
        public array $taxTotals,
    ) {
    }

    /**
     * Returns the totals as payload keys that the XML builders consume.
     *
     * @return array{
     *     total_without_taxes: string,
     *     total_discount: string,
     *     total_amount: string,
     *     tax_totals: list<array<string, string>>
     * }
     */
    public function toArray(): array
    {
        return [
            'total_without_taxes' => $this->totalWithoutTaxes,
            'total_discount' => $this->totalDiscount,
            'total_amount' => $this->importeTotal,
            'tax_totals' => array_map(
                static fn (TaxTotal $tax): array => $tax->toArray(),
                $this->taxTotals,
            ),
        ];
    }
}
