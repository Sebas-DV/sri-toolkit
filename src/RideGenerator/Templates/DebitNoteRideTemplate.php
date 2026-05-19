<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Templates;

use MTZ\Toolkit\RideGenerator\Contracts\RideTemplateInterface;
use MTZ\Toolkit\RideGenerator\Data\RideData;

final readonly class DebitNoteRideTemplate extends BaseRideTemplate implements RideTemplateInterface
{
    public function render(RideData $data): string
    {
        $company = is_array($data->data['company'] ?? null) ? $data->data['company'] : [];
        $customer = is_array($data->data['customer'] ?? null) ? $data->data['customer'] : [];
        $referencedDocument = is_array($data->data['referenced_document'] ?? null) ? $data->data['referenced_document'] : [];

        return $this->html('
<div class="ride">
    ' . $this->header($data, $company) . '

    ' . $this->informationBox('Información de la Nota de Débito', [
            ['Razón Social / Nombres y Apellidos', $customer['name'] ?? ''],
            ['Identificación', $customer['identification_number'] ?? ''],
            ['Fecha Emisión', $data->data['date'] ?? ''],
            ['Comprobante que se modifica', $this->joinValues([$referencedDocument['document_type'] ?? null, $referencedDocument['number'] ?? null])],
            ['Fecha emisión comprobante sustento', $referencedDocument['emission_date'] ?? ''],
        ]) . '

    ' . $this->reasonsTable($data) . '

    <table style="margin-top: 8px;">
        <tr>
            <td style="width: 63%; padding-right: 8px;">
                ' . $this->additionalInfoBox($data) . '
                ' . $this->paymentsBox($data) . '
            </td>
            <td style="width: 37%;">' . $this->totalsTable([
            ['TOTAL SIN IMPUESTOS', $data->data['total_without_taxes'] ?? '0.00'],
            ['ICE', $data->data['ice'] ?? '0.00'],
            ['IVA', $data->data['vat'] ?? $data->data['iva_12'] ?? '0.00'],
            ['VALOR TOTAL', $data->data['total_amount'] ?? '0.00'],
        ]) . '</td>
        </tr>
    </table>
</div>');
    }

    private function reasonsTable(RideData $data): string
    {
        $reasons = is_array($data->data['reasons'] ?? null) ? $data->data['reasons'] : [];
        $rows = '';

        foreach ($reasons as $reason)
        {
            if (! is_array($reason))
            {
                continue;
            }

            $rows .= '
<tr>
    <td>' . $this->e($reason['reason'] ?? '') . '</td>
    <td class="right">' . $this->e($reason['amount'] ?? '') . '</td>
</tr>';
        }

        return '
<table class="items" style="margin-top: 8px;">
    <tr>
        <th>Razón de Modificación</th>
        <th>Valor</th>
    </tr>
    ' . $rows . '
</table>';
    }
}
