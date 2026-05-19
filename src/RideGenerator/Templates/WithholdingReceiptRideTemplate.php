<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Templates;

use MTZ\Toolkit\RideGenerator\Contracts\RideTemplateInterface;
use MTZ\Toolkit\RideGenerator\Data\RideData;

final readonly class WithholdingReceiptRideTemplate extends BaseRideTemplate implements RideTemplateInterface
{
    public function render(RideData $data): string
    {
        $company = is_array($data->data['company'] ?? null) ? $data->data['company'] : [];
        $subject = is_array($data->data['subject'] ?? null) ? $data->data['subject'] : [];

        return $this->html('
<div class="ride">
    ' . $this->header($data, $company) . '

    ' . $this->informationBox('Información del Sujeto Retenido', [
            ['Razón Social / Nombres y Apellidos', $subject['name'] ?? ''],
            ['Identificación', $subject['identification_number'] ?? ''],
            ['Fecha Emisión', $data->data['date'] ?? ''],
            ['Periodo fiscal', $data->data['fiscal_period'] ?? ''],
            ['Parte relacionada', $subject['related_party'] ?? ''],
        ]) . '

    ' . $this->supportingDocumentsTable($data) . '

    <table style="margin-top: 8px;">
        <tr>
            <td>' . $this->additionalInfoBox($data) . '</td>
        </tr>
    </table>
</div>');
    }

    private function supportingDocumentsTable(RideData $data): string
    {
        $documents = is_array($data->data['supporting_documents'] ?? null) ? $data->data['supporting_documents'] : [];
        $rows = '';

        foreach ($documents as $document)
        {
            if (! is_array($document))
            {
                continue;
            }

            $rows .= '
<tr>
    <td>' . $this->e($document['support_code'] ?? '') . '</td>
    <td>' . $this->e($document['document_code'] ?? '') . '</td>
    <td>' . $this->e($document['document_number'] ?? '') . '</td>
    <td>' . $this->e($document['emission_date'] ?? '') . '</td>
    <td class="right">' . $this->e($document['total_without_taxes'] ?? '') . '</td>
    <td class="right">' . $this->e($document['total_amount'] ?? '') . '</td>
    <td>' . $this->e($this->taxSummary($document)) . '</td>
    <td>' . $this->e($this->withholdingSummary($document)) . '</td>
</tr>';
        }

        return '
<table class="items" style="margin-top: 8px;">
    <thead>
        <tr>
            <th>Sustento</th>
            <th>Tipo Doc.</th>
            <th>Número Doc.</th>
            <th>Fecha Emisión</th>
            <th>Base</th>
            <th>Total</th>
            <th>Impuestos</th>
            <th>Retenciones</th>
        </tr>
    </thead>
    <tbody>' . $rows . '</tbody>
</table>';
    }

    private function taxSummary(array $document): string
    {
        $taxes = is_array($document['taxes'] ?? null) ? $document['taxes'] : [];
        $parts = [];

        foreach ($taxes as $tax)
        {
            if (! is_array($tax))
            {
                continue;
            }

            $parts[] = $this->joinValues([$tax['code'] ?? null, $tax['percentage_code'] ?? null], '-') . ': ' . $this->joinValues([$tax['value'] ?? '0.00']);
        }

        return implode(' / ', $parts);
    }

    private function withholdingSummary(array $document): string
    {
        $withholdings = is_array($document['withholdings'] ?? null) ? $document['withholdings'] : [];
        $parts = [];

        foreach ($withholdings as $withholding)
        {
            if (! is_array($withholding))
            {
                continue;
            }

            $parts[] = $this->joinValues([$withholding['code'] ?? null, $withholding['withholding_code'] ?? null], '-') . ': ' . $this->joinValues([$withholding['value'] ?? '0.00']);
        }

        return implode(' / ', $parts);
    }
}
