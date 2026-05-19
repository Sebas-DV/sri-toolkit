<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Templates;

use MTZ\Toolkit\RideGenerator\Contracts\RideTemplateInterface;
use MTZ\Toolkit\RideGenerator\Data\RideData;

final readonly class PurchaseSettlementRideTemplate extends BaseRideTemplate implements RideTemplateInterface
{
    public function render(RideData $data): string
    {
        $company = is_array($data->data['company'] ?? null) ? $data->data['company'] : [];
        $provider = is_array($data->data['provider'] ?? null) ? $data->data['provider'] : [];

        return $this->html('
<div class="ride">
    ' . $this->header($data, $company) . '

    ' . $this->informationBox('Información del Proveedor', [
            ['Razón Social / Nombres y Apellidos', $provider['name'] ?? ''],
            ['Identificación', $provider['identification_number'] ?? ''],
            ['Fecha Emisión', $data->data['date'] ?? ''],
            ['Dirección', $provider['address'] ?? ''],
        ]) . '

    ' . $this->detailsTable($data) . '

    <table style="margin-top: 8px;">
        <tr>
            <td style="width: 63%; padding-right: 8px;">
                ' . $this->additionalInfoBox($data) . '
                ' . $this->paymentsBox($data) . '
            </td>
            <td style="width: 37%;">' . $this->totalsTable($this->commercialTotals($data)) . '</td>
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

            $additional = $this->additionalDetailValues($detail);

            $rows .= '
<tr>
    <td>' . $this->e($detail['main_code'] ?? '') . '</td>
    <td>' . $this->e($detail['auxiliary_code'] ?? '') . '</td>
    <td class="right">' . $this->e($detail['quantity'] ?? '') . '</td>
    <td>' . $this->e($detail['description'] ?? '') . '</td>
    <td>' . $this->e($additional[0] ?? '') . '</td>
    <td>' . $this->e($additional[1] ?? '') . '</td>
    <td>' . $this->e($additional[2] ?? '') . '</td>
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
            <th>Detalle<br>Adicional</th>
            <th>Detalle<br>Adicional</th>
            <th>Detalle<br>Adicional</th>
            <th>Precio<br>Unitario</th>
            <th>Descuento</th>
            <th>Precio Total</th>
        </tr>
    </thead>
    <tbody>' . $rows . '</tbody>
</table>';
    }
}
