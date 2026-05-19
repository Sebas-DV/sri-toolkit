<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Templates;

use MTZ\Toolkit\RideGenerator\Data\RideData;

abstract readonly class BaseRideTemplate
{
    protected function html(string $body): string
    {
        return '
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: dejavusans, sans-serif;
            font-size: 8px;
            color: #000;
        }

        .ride {
            border: 1px solid #222;
            padding: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            vertical-align: top;
        }

        .box {
            border: 1px solid #222;
            padding: 6px;
        }

        .rounded {
            border-radius: 6px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .title {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 4px;
        }

        .section-title {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .small {
            font-size: 6.5px;
        }

        .items th,
        .items td {
            border: 1px solid #222;
            padding: 3px;
            font-size: 6.5px;
        }

        .totals td {
            border: 1px solid #222;
            padding: 3px;
            font-size: 7px;
        }

        .muted {
            color: #333;
        }
    </style>
</head>
<body>
' . $body . '
</body>
</html>';
    }

    protected function header(RideData $data, array $company): string
    {
        return '
<table>
    <tr>
        <td style="width: 50%; padding-right: 5px;">
            ' . $this->logoBox($company) . '
            ' . $this->issuerBox($company, $data) . '
        </td>
        <td style="width: 50%; padding-left: 5px;">
            ' . $this->fiscalBox($data, $company) . '
        </td>
    </tr>
</table>';
    }

    protected function logoBox(array $company): string
    {
        $logo = $company['logo_path'] ?? $company['logo_base64'] ?? null;

        if (! is_string($logo) || $logo === '')
        {
            return '<div class="box center" style="height: 95px;"><br><br>LOGO</div>';
        }

        return '
<div class="box center" style="height: 95px;">
    <img src="' . $this->e($logo) . '" style="max-width: 100%; max-height: 88px;" alt="NO LOGO">
</div>';
    }

    protected function issuerBox(array $company, RideData $data): string
    {
        $payload = $data->data;

        return '
<div class="box rounded" style="height: 95px; margin-top: 7px;">
    <div>' . $this->e($company['legal_name'] ?? '') . '</div>
    <br>
    <div class="bold">Dirección Matriz:</div>
    <div>' . $this->e($company['head_office_address'] ?? '') . '</div>
    <br>
    <div class="bold">Dirección Sucursal:</div>
    <div>' . $this->e($company['establishment_address'] ?? $payload['establishment_address'] ?? '') . '</div>
    <br>
    <table>
        <tr>
            <td class="bold">Contribuyente Especial Nro</td>
            <td class="right">' . $this->e($company['special_taxpayer'] ?? '') . '</td>
        </tr>
        <tr>
            <td class="bold">OBLIGADO A LLEVAR CONTABILIDAD</td>
            <td class="right">' . $this->e($payload['requires_accounting'] ?? $company['requires_accounting'] ?? 'NO') . '</td>
        </tr>
    </table>
</div>';
    }

    protected function fiscalBox(RideData $data, array $company): string
    {
        return '
<div class="box rounded" style="min-height: 205px;">
    <div><span class="bold">R.U.C.:</span> &nbsp; ' . $this->e($company['ruc'] ?? '') . '</div>
    <br>
    <div class="title">' . $this->e($data->documentType->title()) . '</div>
    <br>
    <div><span class="bold">No.</span> &nbsp; ' . $this->e($this->documentNumber($data)) . '</div>
    <br>
    <div class="bold">NÚMERO DE AUTORIZACIÓN</div>
    <div class="small">' . $this->e($data->authorizationNumber ?? $data->accessKey) . '</div>
    <br>
    <table>
        <tr>
            <td class="bold" style="width: 42%;">FECHA Y HORA DE AUTORIZACIÓN</td>
            <td>' . $this->e($data->authorizationDate ?? '') . '</td>
        </tr>
    </table>
    <br>
    <div><span class="bold">AMBIENTE:</span> &nbsp; ' . $this->e($data->data['environment_label'] ?? 'PRUEBAS') . '</div>
    <br>
    <div><span class="bold">EMISIÓN:</span> &nbsp; ' . $this->e($data->data['emission_label'] ?? 'NORMAL') . '</div>
    <br>
    <div>CLAVE DE ACCESO</div>
    <div class="center">
        <barcode code="' . $this->e($data->accessKey) . '" type="C128B" size="0.8" height="1.1" />
    </div>
    <div class="center small">' . $this->e($data->accessKey) . '</div>
</div>';
    }

    protected function e(mixed $value): string
    {
        if ($value === null)
        {
            return '';
        }

        if (! is_scalar($value))
        {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function documentNumber(RideData $data): string
    {
        $payload = $data->data;

        $establishment = is_scalar($payload['establishment_code'] ?? null)
            ? (string) $payload['establishment_code']
            : '001';

        $emissionPoint = is_scalar($payload['emission_point_code'] ?? null)
            ? (string) $payload['emission_point_code']
            : '001';

        $sequential = is_scalar($payload['sequential'] ?? null)
            ? (string) $payload['sequential']
            : '';

        return $establishment . '-' . $emissionPoint . '-' . $sequential;
    }

    /**
     * @param list<array{0: string, 1: mixed}> $rows
     */
    protected function informationBox(string $title, array $rows): string
    {
        return '
<div class="box" style="margin-top: 8px;">
    <div class="section-title">' . $this->e($title) . '</div>
    <table>
        ' . $this->informationRows($rows) . '
    </table>
</div>';
    }

    /**
     * @param list<array{0: string, 1: mixed}> $rows
     */
    protected function informationRows(array $rows): string
    {
        $html = '';

        foreach ($rows as $row)
        {
            $html .= '
<tr>
    <td class="bold" style="width: 32%; padding: 2px 0;">' . $this->e($row[0]) . '</td>
    <td style="padding: 2px 0;">' . $this->e($row[1]) . '</td>
</tr>';
        }

        return $html;
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

        if ($rows === '')
        {
            $rows = '<tr><td class="muted">Sin información adicional</td></tr>';
        }

        return '
<div class="box" style="min-height: 70px;">
    <div class="section-title">Información Adicional</div>
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

        if ($rows === '')
        {
            return '';
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

    /**
     * @param list<array{0: string, 1: mixed}> $rows
     */
    protected function totalsTable(array $rows): string
    {
        $html = '';

        foreach ($rows as $row)
        {
            $html .= '<tr><td>' . $this->e($row[0]) . '</td><td class="right">' . $this->e($row[1]) . '</td></tr>';
        }

        return '<table class="totals">' . $html . '</table>';
    }

    /**
     * @return list<array{0: string, 1: mixed}>
     */
    protected function commercialTotals(RideData $data): array
    {
        return [
            ['SUBTOTAL 12%', $data->data['subtotal_12'] ?? '0.00'],
            ['SUBTOTAL IVA 0%', $data->data['subtotal_0'] ?? '0.00'],
            ['SUBTOTAL NO OBJETO IVA', $data->data['subtotal_not_subject_to_vat'] ?? '0.00'],
            ['SUBTOTAL EXENTO IVA', $data->data['subtotal_exempt_vat'] ?? '0.00'],
            ['SUBTOTAL SIN IMPUESTOS', $data->data['total_without_taxes'] ?? '0.00'],
            ['DESCUENTO', $data->data['total_discount'] ?? '0.00'],
            ['ICE', $data->data['ice'] ?? '0.00'],
            ['IVA 12%', $data->data['iva_12'] ?? $data->data['vat'] ?? '0.00'],
            ['IRBPNR', $data->data['irbpnr'] ?? '0.00'],
            ['PROPINA', $data->data['tip'] ?? '0.00'],
            ['VALOR TOTAL', $data->data['total_amount'] ?? '0.00'],
        ];
    }

    /**
     * @return list<mixed>
     */
    protected function additionalDetailValues(array $detail): array
    {
        if (is_array($detail['additional_info'] ?? null))
        {
            return array_slice(array_values($detail['additional_info']), 0, 3);
        }

        return [
            $detail['additional_1'] ?? '',
            $detail['additional_2'] ?? '',
            $detail['additional_3'] ?? '',
        ];
    }

    /**
     * @param list<mixed> $values
     */
    protected function joinValues(array $values, string $separator = ' '): string
    {
        $parts = [];

        foreach ($values as $value)
        {
            if (is_scalar($value) && (string) $value !== '')
            {
                $parts[] = (string) $value;
            }
        }

        return implode($separator, $parts);
    }
}
