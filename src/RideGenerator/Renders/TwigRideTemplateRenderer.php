<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Renders;

use MTZ\Toolkit\RideGenerator\Config\RidePdfConfig;
use MTZ\Toolkit\RideGenerator\Contracts\RideTemplateRendererInterface;
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final readonly class TwigRideTemplateRenderer implements RideTemplateRendererInterface
{
    private const INVOICE_TAX_RATES = [5, 8, 12, 13, 14, 15];

    private const PAYMENT_METHODS = [
        '01' => 'SIN UTILIZACIÓN DEL SISTEMA FINANCIERO',
        '15' => 'COMPENSACIÓN DE DEUDAS',
        '16' => 'TARJETA DE DÉBITO',
        '17' => 'DINERO ELECTRÓNICO',
        '18' => 'TARJETA PREPAGO',
        '19' => 'TARJETA DE CRÉDITO',
        '20' => 'OTROS CON UTILIZACIÓN DEL SISTEMA FINANCIERO',
        '21' => 'ENDOSO DE TÍTULOS',
    ];

    private Environment $twig;

    public function __construct(
        private RidePdfConfig $config = new RidePdfConfig(),
    ) {
        $templatesPath = $this->config->templatesPath
            ?? dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'views';

        $this->twig = new Environment(
            new FilesystemLoader($templatesPath),
            [
                'autoescape' => 'html',
                'strict_variables' => true,
                'cache' => false,
                'auto_reload' => true,
            ],
        );
    }

    public function render(RideData $data): string
    {
        $company = $data->data['company'] ?? [];

        return $this->twig->render(
            $this->template($data->documentType),
            [
                'ride' => $data,
                'data' => $data->data,
                'accessKey' => $data->accessKey,
                'authorizationNumber' => $data->authorizationNumber ?? $data->accessKey,
                'authorizationDate' => $data->authorizationDate,
                'documentTitle' => $data->documentType->title(),
                'documentNumber' => $this->documentNumber($data),
                'company' => $company,
                'companyLogo' => $this->companyLogo($company),
                'accessKeyBarcode' => $this->barcodeDataUri($data->accessKey),
                'environmentLabel' => $this->environmentLabel($data),
                'emissionLabel' => $this->emissionLabel($data),
                'invoiceTotals' => $this->invoiceTotals($data->data),
                'purchaseSettlementTotals' => $this->invoiceTotals($data->data),
                'creditNoteTotals' => $this->creditNoteTotals($data->data),
                'debitNoteTotals' => $this->debitNoteTotals($data->data),
                'referencedDocumentTitle' => $this->referencedDocumentTitle($data->data),
                'withholdingRows' => $this->withholdingRows($data->data),
                'deliveryGuideRecipients' => $this->deliveryGuideRecipients($data->data),
                'paymentMethods' => self::PAYMENT_METHODS,
            ],
        );
    }

    private function barcodeDataUri(string $value): string
    {
        $generator = new BarcodeGeneratorPNG();
        $png = $generator->getBarcode(
            $value,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            2,
            50,
        );

        return 'data:image/png;base64,' . base64_encode($png);
    }

    private function documentNumber(RideData $data): string
    {
        $establishment = $data->data['establishment'] ?? [];
        $emissionPoint = $data->data['emission_point'] ?? [];
        $establishmentCode = (string) ($data->data['establishment_code'] ?? $establishment['code'] ?? '001');
        $emissionPointCode = (string) ($data->data['emission_point_code'] ?? $emissionPoint['code'] ?? '001');
        $sequential = (string) ($data->data['sequential'] ?? '');

        if ($sequential === '')
        {
            $sequential = substr($data->accessKey, 30, 9) ?: '000000000';
        }

        return $establishmentCode . '-' . $emissionPointCode . '-' . $sequential;
    }

    /**
     * @param array<string, mixed> $company
     */
    private function companyLogo(array $company): string
    {
        $base64 = $this->scalarString($company['logo_base64'] ?? '');

        if ($base64 !== '')
        {
            return str_starts_with($base64, 'data:image/')
                ? $base64
                : 'data:image/png;base64,' . $base64;
        }

        $path = $this->scalarString($company['logo_path'] ?? '');

        if ($path === '' || ! is_file($path) || ! is_readable($path))
        {
            return '';
        }

        $contents = file_get_contents($path);

        if ($contents === false)
        {
            return '';
        }

        $mimeType = match (strtolower(pathinfo($path, PATHINFO_EXTENSION)))
        {
            'gif' => 'image/gif',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    private function environmentLabel(RideData $data): string
    {
        $label = (string) ($data->data['environment_label'] ?? '');

        if ($label !== '')
        {
            return $label;
        }

        return match (substr($data->accessKey, 23, 1))
        {
            '2' => 'PRODUCCIÓN',
            default => 'PRUEBAS',
        };
    }

    private function emissionLabel(RideData $data): string
    {
        $label = (string) ($data->data['emission_label'] ?? '');

        if ($label !== '')
        {
            return $label;
        }

        return match (substr($data->accessKey, 47, 1))
        {
            '1' => 'NORMAL',
            default => 'NORMAL',
        };
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     subtotal_special: string,
     *     subtotal_rates: list<array{rate: int, value: string}>,
     *     subtotal_zero: string,
     *     subtotal_not_subject: string,
     *     subtotal_exempt: string,
     *     total_without_taxes: string,
     *     total_discount: string,
     *     ice: string,
     *     vat_rates: list<array{rate: int, value: string}>,
     *     vat_refund: string,
     *     irbpnr: string,
     *     total: string
     * }
     */
    private function invoiceTotals(array $data): array
    {
        $taxableBases = [];
        $taxValues = [];
        $ice = 0.0;
        $irbpnr = 0.0;
        $vatRefund = 0.0;
        $taxTotals = $data['tax_totals'] ?? [];
        $ratesByPercentageCode = $this->taxRatesByPercentageCode($data);

        if (is_iterable($taxTotals))
        {
            foreach ($taxTotals as $tax)
            {
                if (! is_array($tax))
                {
                    continue;
                }

                $code = $this->scalarString($tax['code'] ?? '');
                $value = $this->numeric($tax['value'] ?? 0);

                if ($code === '2')
                {
                    $percentageCode = $this->scalarString($tax['percentage_code'] ?? '');
                    $rate = $this->taxRate($tax['rate'] ?? null)
                        ?? $ratesByPercentageCode[$percentageCode]
                        ?? null;

                    if ($rate !== null)
                    {
                        $taxableBases[$rate] = ($taxableBases[$rate] ?? 0.0)
                            + $this->numeric($tax['taxable_base'] ?? 0);
                        $taxValues[$rate] = ($taxValues[$rate] ?? 0.0) + $value;
                    }

                    $vatRefund += $this->numeric($tax['refund_value'] ?? 0);
                } elseif ($code === '3')
                {
                    $ice += $value;
                } elseif ($code === '5')
                {
                    $irbpnr += $value;
                }
            }
        }

        $rateBases = [];
        $rateTaxes = [];

        foreach (self::INVOICE_TAX_RATES as $rate)
        {
            $rateBases[$rate] = $this->numericFromKeys(
                $data,
                ['subtotal_' . $rate],
                $taxableBases[$rate] ?? 0.0,
            );
            $rateTaxes[$rate] = $this->numericFromKeys(
                $data,
                ['iva_' . $rate],
                $taxValues[$rate] ?? 0.0,
            );
        }

        $genericVat = $this->numericFromKeys($data, ['vat'], 0.0);

        if ($genericVat !== 0.0 && array_sum($rateTaxes) === 0.0)
        {
            $activeRates = array_keys(array_filter(
                $rateBases,
                static fn (float $value): bool => $value !== 0.0,
            ));
            $targetRate = count($activeRates) === 1 ? $activeRates[0] : 15;
            $rateTaxes[$targetRate] = $genericVat;
        }

        $subtotalRates = [];
        $vatRates = [];

        foreach (self::INVOICE_TAX_RATES as $rate)
        {
            $mustDisplay = in_array($rate, [5, 15], true)
                || $rateBases[$rate] !== 0.0
                || $rateTaxes[$rate] !== 0.0;

            if (! $mustDisplay)
            {
                continue;
            }

            $subtotalRates[] = [
                'rate' => $rate,
                'value' => $this->money($rateBases[$rate]),
            ];
            $vatRates[] = [
                'rate' => $rate,
                'value' => $this->money($rateTaxes[$rate]),
            ];
        }

        return [
            'subtotal_special' => $this->money($this->numericFromKeys($data, ['subtotal_special'], 0.0)),
            'subtotal_rates' => $subtotalRates,
            'subtotal_zero' => $this->money($this->numericFromKeys(
                $data,
                ['subtotal_0'],
                $taxableBases[0] ?? 0.0,
            )),
            'subtotal_not_subject' => $this->money($this->numericFromKeys(
                $data,
                ['subtotal_no_iva', 'subtotal_not_subject_to_vat'],
                0.0,
            )),
            'subtotal_exempt' => $this->money($this->numericFromKeys(
                $data,
                ['subtotal_exento', 'subtotal_exempt_vat', 'subtotal_exento_iva'],
                0.0,
            )),
            'total_without_taxes' => $this->money($this->numericFromKeys($data, ['total_without_taxes'], 0.0)),
            'total_discount' => $this->money($this->numericFromKeys($data, ['total_discount'], 0.0)),
            'ice' => $this->money($this->numericFromKeys($data, ['ice'], $ice)),
            'vat_rates' => $vatRates,
            'vat_refund' => $this->money($this->numericFromKeys(
                $data,
                ['total_devolucion_iva'],
                $vatRefund,
            )),
            'irbpnr' => $this->money($this->numericFromKeys($data, ['irbpnr'], $irbpnr)),
            'total' => $this->money($this->numericFromKeys($data, ['total_amount'], 0.0)),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     subtotal_special: string,
     *     subtotal_rates: list<array{rate: int, value: string}>,
     *     subtotal_zero: string,
     *     subtotal_not_subject: string,
     *     subtotal_exempt: string,
     *     total_without_taxes: string,
     *     total_discount: string,
     *     ice: string,
     *     vat_rates: list<array{rate: int, value: string}>,
     *     vat_refund: string,
     *     irbpnr: string,
     *     vat_special: string,
     *     total: string
     * }
     */
    private function creditNoteTotals(array $data): array
    {
        $totals = $this->invoiceTotals($data);
        $totals['vat_special'] = $this->money($this->numericFromKeys($data, ['iva_special'], 0.0));
        $totals['total'] = $this->money($this->numericFromKeys(
            $data,
            ['modified_document_total', 'total_amount'],
            0.0,
        ));

        return $totals;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     subtotal_special: string,
     *     subtotal_rates: list<array{rate: int, value: string}>,
     *     subtotal_zero: string,
     *     subtotal_not_subject: string,
     *     subtotal_exempt: string,
     *     total_without_taxes: string,
     *     total_discount: string,
     *     ice: string,
     *     vat_rates: list<array{rate: int, value: string}>,
     *     vat_refund: string,
     *     irbpnr: string,
     *     vat_special: string,
     *     total: string
     * }
     */
    private function debitNoteTotals(array $data): array
    {
        $totals = $this->invoiceTotals($data);
        $totals['vat_special'] = $this->money($this->numericFromKeys($data, ['iva_special'], 0.0));
        $totals['total'] = $this->money($this->numericFromKeys($data, ['total_amount'], 0.0));

        return $totals;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function referencedDocumentTitle(array $data): string
    {
        $referenced = $data['referenced_document'] ?? [];

        if (! is_array($referenced))
        {
            return '';
        }

        $label = $this->scalarString($referenced['document_type_label'] ?? '');

        if ($label !== '')
        {
            return $label;
        }

        return $this->documentTypeTitle(
            $this->scalarString($referenced['document_type'] ?? ''),
        );
    }

    private function documentTypeTitle(string $code): string
    {
        return match ($code)
        {
            '01' => 'FACTURA',
            '03' => 'LIQUIDACIÓN DE COMPRA DE BIENES Y PRESTACIÓN DE SERVICIOS',
            '04' => 'NOTA DE CRÉDITO',
            '05' => 'NOTA DE DÉBITO',
            '06' => 'GUÍA DE REMISIÓN',
            '07' => 'COMPROBANTE DE RETENCIÓN',
            default => $code,
        };
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array{
     *     document_title: string,
     *     document_number: string,
     *     emission_date: string,
     *     fiscal_period: string,
     *     taxable_base: string,
     *     tax_label: string,
     *     percentage: string,
     *     value: string
     * }>
     */
    private function withholdingRows(array $data): array
    {
        $rows = [];
        $fiscalPeriod = $this->scalarString($data['fiscal_period'] ?? '');
        $documents = $data['supporting_documents'] ?? [];

        if (! is_iterable($documents))
        {
            return $rows;
        }

        foreach ($documents as $document)
        {
            if (! is_array($document))
            {
                continue;
            }

            $documentCode = $this->scalarString($document['document_code'] ?? '');
            $documentTitle = $this->scalarString($document['document_type_label'] ?? '');

            if ($documentTitle === '')
            {
                $documentTitle = $this->documentTypeTitle($documentCode);
            }

            $withholdings = $document['withholdings'] ?? [];

            if (! is_iterable($withholdings))
            {
                continue;
            }

            foreach ($withholdings as $withholding)
            {
                if (! is_array($withholding))
                {
                    continue;
                }

                $percentage = $this->scalarString($withholding['percentage'] ?? '');
                $taxableBase = $withholding['taxable_base']
                    ?? $withholding['tax_base']
                    ?? $document['total_without_taxes']
                    ?? 0;

                $rows[] = [
                    'document_title' => $documentTitle,
                    'document_number' => $this->formatDocumentNumber(
                        $this->scalarString($document['document_number'] ?? ''),
                    ),
                    'emission_date' => $this->scalarString($document['emission_date'] ?? ''),
                    'fiscal_period' => $fiscalPeriod,
                    'taxable_base' => $this->money($this->numeric($taxableBase)),
                    'tax_label' => $this->withholdingTaxLabel(
                        $this->scalarString($withholding['code'] ?? ''),
                    ),
                    'percentage' => $percentage === '' ? '0.00' : $percentage,
                    'value' => $this->money($this->numeric($withholding['value'] ?? 0)),
                ];
            }
        }

        return $rows;
    }

    private function withholdingTaxLabel(string $code): string
    {
        return match ($code)
        {
            '1' => 'RENTA',
            '2' => 'IVA',
            '6' => 'ISD',
            default => $code,
        };
    }

    private function formatDocumentNumber(string $number): string
    {
        if (preg_match('/^\d{15}$/', $number) !== 1)
        {
            return $number;
        }

        return substr($number, 0, 3)
            . '-' . substr($number, 3, 3)
            . '-' . substr($number, 6);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    private function deliveryGuideRecipients(array $data): array
    {
        $result = [];
        $recipients = $data['recipients'] ?? [];

        if (! is_iterable($recipients))
        {
            return $result;
        }

        foreach ($recipients as $recipient)
        {
            if (! is_array($recipient))
            {
                continue;
            }

            $documentCode = $this->scalarString($recipient['supporting_document_code'] ?? '');
            $documentTitle = $this->scalarString($recipient['supporting_document_type_label'] ?? '');

            if ($documentTitle === '')
            {
                $documentTitle = $this->documentTypeTitle($documentCode);
            }

            $recipient['supporting_document_title'] = $documentTitle;
            $recipient['supporting_document_number_formatted'] = $this->formatDocumentNumber(
                $this->scalarString($recipient['supporting_document_number'] ?? ''),
            );
            $result[] = $recipient;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, int>
     */
    private function taxRatesByPercentageCode(array $data): array
    {
        $rates = [];
        $details = $data['details'] ?? [];

        if (! is_iterable($details))
        {
            return $rates;
        }

        foreach ($details as $detail)
        {
            if (! is_array($detail))
            {
                continue;
            }

            $taxes = $detail['taxes'] ?? [];

            if (! is_iterable($taxes))
            {
                continue;
            }

            foreach ($taxes as $tax)
            {
                if (! is_array($tax) || $this->scalarString($tax['code'] ?? '') !== '2')
                {
                    continue;
                }

                $percentageCode = $this->scalarString($tax['percentage_code'] ?? '');
                $rate = $this->taxRate($tax['rate'] ?? null);

                if ($percentageCode !== '' && $rate !== null)
                {
                    $rates[$percentageCode] = $rate;
                }
            }
        }

        return $rates;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $keys
     */
    private function numericFromKeys(array $data, array $keys, float $fallback): float
    {
        foreach ($keys as $key)
        {
            if (array_key_exists($key, $data))
            {
                return $this->numeric($data[$key]);
            }
        }

        return $fallback;
    }

    private function numeric(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function scalarString(mixed $value): string
    {
        if (is_string($value))
        {
            return $value;
        }

        return is_int($value) || is_float($value) ? (string) $value : '';
    }

    private function taxRate(mixed $rate): ?int
    {
        if (! is_numeric($rate))
        {
            return null;
        }

        return (int) round((float) $rate);
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function template(RideDocumentType $documentType): string
    {
        return match ($documentType)
        {
            RideDocumentType::Invoice => 'invoice.html.twig',
            RideDocumentType::CreditNote => 'credit-note.html.twig',
            RideDocumentType::DebitNote => 'debit-note.html.twig',
            RideDocumentType::WithholdingReceipt => 'withholding-receipt.html.twig',
            RideDocumentType::DeliveryGuide => 'delivery-guide.html.twig',
            RideDocumentType::PurchaseSettlement => 'purchase-settlement.html.twig',
        };
    }
}
