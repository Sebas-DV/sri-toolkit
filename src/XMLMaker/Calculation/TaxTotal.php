<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Calculation;

/**
 * A single aggregated tax line for the document totals (totalConImpuestos).
 */
final readonly class TaxTotal
{
    /**
     * @param string $code The tax code (e.g. '2' IVA, '3' ICE, '5' IRBPNR).
     * @param string $percentageCode The percentage code (tarifa) for the tax.
     * @param string $taxableBase The aggregated taxable base, formatted to 2 decimals.
     * @param string $value The aggregated tax value, formatted to 2 decimals.
     * @param string|null $rate The tax rate, when known.
     * @param string|null $refundValue The aggregated VAT refund amount (valorDevolucionIva), when present.
     */
    public function __construct(
        public string $code,
        public string $percentageCode,
        public string $taxableBase,
        public string $value,
        public ?string $rate = null,
        public ?string $refundValue = null,
    ) {
    }

    /**
     * @return array<string, string> Payload shape consumed by the XML builders.
     */
    public function toArray(): array
    {
        $array = [
            'code' => $this->code,
            'percentage_code' => $this->percentageCode,
            'taxable_base' => $this->taxableBase,
            'value' => $this->value,
        ];

        if ($this->rate !== null)
        {
            $array['rate'] = $this->rate;
        }

        if ($this->refundValue !== null)
        {
            $array['refund_value'] = $this->refundValue;
        }

        return $array;
    }
}
