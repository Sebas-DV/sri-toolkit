<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Templates;

use MTZ\Toolkit\RideGenerator\Contracts\RideTemplateInterface;
use MTZ\Toolkit\RideGenerator\Data\RideData;

final readonly class CreditNoteRideTemplate extends BaseRideTemplate implements RideTemplateInterface
{
    public function render(RideData $data): string
    {
        $company = is_array($data->data['company'] ?? null) ? $data->data['company'] : [];
        $customer = is_array($data->data['customer'] ?? null) ? $data->data['customer'] : [];
        $referencedDocument = is_array($data->data['referenced_document'] ?? null) ? $data->data['referenced_document'] : [];

        return $this->html('
<div class="ride">
    ' . $this->header($data, $company) . '

    ' . $this->informationBox('Información de la Nota de Crédito', [
            ['Razón Social / Nombres y Apellidos', $customer['name'] ?? ''],
            ['Identificación', $customer['identification_number'] ?? ''],
            ['Fecha Emisión', $data->data['date'] ?? ''],
            ['Comprobante que se modifica', $this->joinValues([$referencedDocument['document_type'] ?? null, $referencedDocument['number'] ?? null])],
            ['Fecha emisión comprobante sustento', $referencedDocument['emission_date'] ?? ''],
            ['Motivo', $referencedDocument['reason'] ?? $data->data['reason'] ?? ''],
        ]) . '

    ' . $this->detailsTable($data) . '

    <table style="margin-top: 8px;">
        <tr>
            <td style="width: 63%; padding-right: 8px;">' . $this->additionalInfoBox($data) . '</td>
            <td style="width: 37%;">' . $this->totalsTable([
            ['SUBTOTAL SIN IMPUESTOS', $data->data['total_without_taxes'] ?? '0.00'],
            ['ICE', $data->data['ice'] ?? '0.00'],
            ['IVA', $data->data['vat'] ?? $data->data['iva_12'] ?? '0.00'],
            ['VALOR MODIFICACIÓN', $data->data['modified_document_total'] ?? $data->data['total_amount'] ?? '0.00'],
            ['MONEDA', $data->data['currency'] ?? 'DOLAR'],
        ]) . '</td>
        </tr>
    </table>
</div>');
    }

    private function detailsTable(RideData $data): string
    {
        $details = is_array($data->data['details'] ?? null) ? $data->data['details'] : [];
        $rows = '';

        foreach ($details as $detail)
        {
            if (! is_array($detail))
            {
                continue;
            }

            $rows .= '
<tr>
    <td>' . $this->e($detail['main_code'] ?? $detail['internal_code'] ?? '') . '</td>
    <td>' . $this->e($detail['auxiliary_code'] ?? $detail['additional_code'] ?? '') . '</td>
    <td class="right">' . $this->e($detail['quantity'] ?? '') . '</td>
    <td>' . $this->e($detail['description'] ?? '') . '</td>
    <td class="right">' . $this->e($detail['unit_price'] ?? '') . '</td>
    <td class="right">' . $this->e($detail['discount'] ?? '0.00') . '</td>
    <td class="right">' . $this->e($detail['total_without_tax'] ?? '') . '</td>
</tr>';
        }

        return '
<table class="items" style="margin-top: 8px;">
    <thead>
        <tr>
            <th>Cód.<br>Principal</th>
            <th>Cód.<br>Auxiliar</th>
            <th>Cant</th>
            <th>Descripción</th>
            <th>Precio<br>Unitario</th>
            <th>Descuento</th>
            <th>Precio Total</th>
        </tr>
    </thead>
    <tbody>' . $rows . '</tbody>
</table>';
    }
}
