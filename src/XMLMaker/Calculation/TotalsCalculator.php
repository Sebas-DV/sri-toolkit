<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Calculation;

use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Money;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;

/**
 * Computes document totals from the detail lines, the single source of truth for
 * both XML generation and the RIDE.
 */
final class TotalsCalculator
{
    /** @var string ISO currency used for the exact-decimal arithmetic (SRI is single-currency). */
    private const CURRENCY = 'USD';

    /** @var string Tax code for ICE. */
    private const CODE_ICE = '3';

    /** @var string Tax code for IRBPNR. */
    private const CODE_IRBPNR = '5';

    /**
     * Calculates the totals for a set of detail lines.
     *
     * @param list<mixed> $details The document detail lines.
     * @param float $tip Optional tip (propina) added to the grand total.
     * @return DocumentTotals
     * @throws MathException
     */
    public function calculate(array $details, float $tip = 0.0): DocumentTotals
    {
        $totalWithoutTaxes = Money::zero(self::CURRENCY);
        $totalDiscount = Money::zero(self::CURRENCY);
        $ice = Money::zero(self::CURRENCY);
        $irbpnr = Money::zero(self::CURRENCY);

        /** @var array<string, array{code: string, percentage_code: string, rate: ?string, base: Money, value: Money, refund: Money, has_refund: bool}> $groups */
        $groups = [];

        foreach ($details as $detail)
        {
            if (! is_array($detail))
            {
                continue;
            }

            $reader = new ArrayReader($detail);

            $totalWithoutTaxes = $totalWithoutTaxes->plus($this->money($reader->nullableString('total_without_tax')), RoundingMode::HalfUp);
            $totalDiscount = $totalDiscount->plus($this->money($reader->nullableString('discount')), RoundingMode::HalfUp);

            foreach ($reader->nullableArray('taxes') as $tax)
            {
                if (! is_array($tax))
                {
                    continue;
                }

                $taxReader = new ArrayReader($tax);

                $code = $taxReader->string('code');
                $percentageCode = $taxReader->string('percentage_code');
                $base = $this->money($taxReader->nullableString('taxable_base'));
                $value = $this->money($taxReader->nullableString('value'));
                $rate = $taxReader->nullableString('rate');
                $refund = $taxReader->nullableString('refund_value');

                if ($code === self::CODE_ICE)
                {
                    $ice = $ice->plus($value, RoundingMode::HalfUp);
                } elseif ($code === self::CODE_IRBPNR)
                {
                    $irbpnr = $irbpnr->plus($value, RoundingMode::HalfUp);
                }

                $key = $code . '|' . $percentageCode;

                if (! isset($groups[$key]))
                {
                    $groups[$key] = [
                        'code' => $code,
                        'percentage_code' => $percentageCode,
                        'rate' => $rate,
                        'base' => Money::zero(self::CURRENCY),
                        'value' => Money::zero(self::CURRENCY),
                        'refund' => Money::zero(self::CURRENCY),
                        'has_refund' => false,
                    ];
                }

                $groups[$key]['base'] = $groups[$key]['base']->plus($base, RoundingMode::HalfUp);
                $groups[$key]['value'] = $groups[$key]['value']->plus($value, RoundingMode::HalfUp);

                if ($refund !== null)
                {
                    $groups[$key]['refund'] = $groups[$key]['refund']->plus($this->money($refund), RoundingMode::HalfUp);
                    $groups[$key]['has_refund'] = true;
                }
            }
        }

        $taxTotals = [];
        $taxSum = Money::zero(self::CURRENCY);

        foreach ($groups as $group)
        {
            $taxSum = $taxSum->plus($group['value'], RoundingMode::HalfUp);

            $taxTotals[] = new TaxTotal(
                code: $group['code'],
                percentageCode: $group['percentage_code'],
                taxableBase: $this->format($group['base']),
                value: $this->format($group['value']),
                rate: $group['rate'],
                refundValue: $group['has_refund'] ? $this->format($group['refund']) : null,
            );
        }

        $importeTotal = $totalWithoutTaxes
            ->plus($taxSum, RoundingMode::HalfUp)
            ->plus($this->money((string) $tip), RoundingMode::HalfUp);

        return new DocumentTotals(
            totalWithoutTaxes: $this->format($totalWithoutTaxes),
            totalDiscount: $this->format($totalDiscount),
            ice: $this->format($ice),
            irbpnr: $this->format($irbpnr),
            importeTotal: $this->format($importeTotal),
            taxTotals: $taxTotals,
        );
    }

    /**
     * @throws MathException
     */
    private function money(?string $value): Money
    {
        if ($value === null || $value === '')
        {
            return Money::zero(self::CURRENCY);
        }

        return Money::of($value, self::CURRENCY, new DefaultContext(), RoundingMode::HalfUp);
    }

    private function format(Money $money): string
    {
        return (string) $money->getAmount();
    }
}
