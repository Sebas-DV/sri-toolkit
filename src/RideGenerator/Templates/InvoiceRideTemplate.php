<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Templates;

use MTZ\Toolkit\RideGenerator\Contracts\RideTemplateInterface;
use MTZ\Toolkit\RideGenerator\Data\RideData;

final readonly class InvoiceRideTemplate extends BaseRideTemplate implements RideTemplateInterface
{
    public function render(RideData $data): string
    {
        $company = is_array($data->data['company'] ?? null) ? $data->data['company'] : [];
        $customer = is_array($data->data['customer'] ?? null) ? $data->data['customer'] : [];

        return $this->html('
<div class="ride">
    ' . $this->header($data, $company) . '

    <div class="box" style="margin-top: 8px;">
        <table>
            <tr>
                <td style="width: 70%;">
                    <span class="bold">Razón Social / Nombres y Apellidos:</span>
                    ' . $this->e($customer['name'] ?? '') . '
                </td>
                <td>
                    <span class="bold">Identificación:</span>
                    ' . $this->e($customer['identification_number'] ?? '') . '
                </td>
            </tr>
            <tr>
                <td>
                    <span class="bold">Fecha Emisión:</span>
                    ' . $this->e($data->data['date'] ?? '') . '
                </td>
                <td>
                    <span class="bold">Guía Remisión:</span>
                    ' . $this->e($data->data['delivery_guide'] ?? '') . '
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="bold">Dirección:</span>
                    ' . $this->e($customer['address'] ?? '') . '
                </td>
            </tr>
        </table>
    </div>

    ' . $this->detailsTable($data) . '

    <table style="margin-top: 8px;">
        <tr>
            <td style="width: 63%; padding-right: 8px;">
                ' . $this->additionalInfoBox($data) . '
                ' . $this->paymentsBox($data) . '
            </td>
            <td style="width: 37%;">
                ' . $this->totalsBox($data) . '
            </td>
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
    <td>' . $this->e($detail['main_code'] ?? '') . '</td>
    <td>' . $this->e($detail['auxiliary_code'] ?? '') . '</td>
    <td class="right">' . $this->e($detail['quantity'] ?? '') . '</td>
    <td>' . $this->e($detail['description'] ?? '') . '</td>
    <td>' . $this->e($detail['additional_1'] ?? '') . '</td>
    <td>' . $this->e($detail['additional_2'] ?? '') . '</td>
    <td>' . $this->e($detail['additional_3'] ?? '') . '</td>
    <td class="right">' . $this->e($detail['unit_price'] ?? '') . '</td>
    <td class="right">' . $this->e($detail['subsidy'] ?? '0.00') . '</td>
    <td class="right">' . $this->e($detail['price_without_subsidy'] ?? '0.00') . '</td>
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
            <th>Subsidio</th>
            <th>Precio Sin<br>Subsidio</th>
            <th>Descuento</th>
            <th>Precio Total</th>
        </tr>
    </thead>
    <tbody>
        ' . $rows . '
    </tbody>
</table>';
    }

    protected function additionalInfoBox(RideData $data): string
    {
        $info = is_array($data->data['additional_info'] ?? null) ? $data->data['additional_info'] : [];

        $rows = '';

        foreach ($info as $name => $value)
        {
            $rows .= '
<tr>
    <td style="width: 35%; padding: 3px;">' . $this->e($name) . '</td>
    <td style="padding: 3px;">' . $this->e($value) . '</td>
</tr>';
        }

        return '
<div class="box" style="min-height: 95px;">
    <div class="bold">Información Adicional</div>
    <br>
    <table>' . $rows . '</table>
</div>';
    }

    protected function paymentsBox(RideData $data): string
    {
        $payments = is_array($data->data['payments'] ?? null) ? $data->data['payments'] : [];

        $rows = '';

        foreach ($payments as $payment)
        {
            if (! is_array($payment))
            {
                continue;
            }

            $rows .= '
<tr>
    <td>' . $this->e($payment['method_label'] ?? $payment['method'] ?? '') . '</td>
    <td class="right">' . $this->e($payment['total'] ?? '') . '</td>
</tr>';
        }

        return '
<table class="items" style="margin-top: 8px;">
    <tr>
        <th>Forma de Pago</th>
        <th>Valor</th>
    </tr>
    ' . $rows . '
</table>';
    }

    private function totalsBox(RideData $data): string
    {
        return '
<table class="totals">
    <tr><td>SUBTOTAL 12%</td><td class="right">' . $this->e($data->data['subtotal_12'] ?? '0.00') . '</td></tr>
    <tr><td>SUBTOTAL IVA 0%</td><td class="right">' . $this->e($data->data['subtotal_0'] ?? '0.00') . '</td></tr>
    <tr><td>SUBTOTAL NO OBJETO IVA</td><td class="right">' . $this->e($data->data['subtotal_not_subject_to_vat'] ?? '0.00') . '</td></tr>
    <tr><td>SUBTOTAL EXENTO IVA</td><td class="right">' . $this->e($data->data['subtotal_exempt_vat'] ?? '0.00') . '</td></tr>
    <tr><td>SUBTOTAL SIN IMPUESTOS</td><td class="right">' . $this->e($data->data['total_without_taxes'] ?? '0.00') . '</td></tr>
    <tr><td>DESCUENTO</td><td class="right">' . $this->e($data->data['total_discount'] ?? '0.00') . '</td></tr>
    <tr><td>ICE</td><td class="right">' . $this->e($data->data['ice'] ?? '0.00') . '</td></tr>
    <tr><td>IVA 12%</td><td class="right">' . $this->e($data->data['iva_12'] ?? '0.00') . '</td></tr>
    <tr><td>IRBPNR</td><td class="right">' . $this->e($data->data['irbpnr'] ?? '0.00') . '</td></tr>
    <tr><td>PROPINA</td><td class="right">' . $this->e($data->data['tip'] ?? '0.00') . '</td></tr>
    <tr><td>VALOR TOTAL</td><td class="right">' . $this->e($data->data['total_amount'] ?? '0.00') . '</td></tr>
    <tr><td class="bold">VALOR TOTAL SIN SUBSIDIO</td><td class="right bold">' . $this->e($data->data['total_without_subsidy'] ?? '0.00') . '</td></tr>
    <tr><td class="bold">AHORRO POR SUBSIDIO:<br><span class="small">(Incluye IVA cuando corresponda)</span></td><td class="right bold">' . $this->e($data->data['subsidy_saving'] ?? '0.00') . '</td></tr>
</table>';
    }
}
