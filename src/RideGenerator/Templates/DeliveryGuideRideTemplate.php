<?php

declare(strict_types=1);

namespace MTZ\Toolkit\RideGenerator\Templates;

use MTZ\Toolkit\RideGenerator\Contracts\RideTemplateInterface;
use MTZ\Toolkit\RideGenerator\Data\RideData;

final readonly class DeliveryGuideRideTemplate extends BaseRideTemplate implements RideTemplateInterface
{
    public function render(RideData $data): string
    {
        $company = is_array($data->data['company'] ?? null) ? $data->data['company'] : [];
        $carrier = is_array($data->data['carrier'] ?? null) ? $data->data['carrier'] : [];
        $shipping = is_array($data->data['shipping'] ?? null) ? $data->data['shipping'] : [];

        return $this->html('
<div class="ride">
    ' . $this->header($data, $company) . '

    ' . $this->informationBox('Información del Transporte', [
            ['Dirección de partida', $shipping['start_address'] ?? ''],
            ['Razón Social / Nombres y Apellidos', $carrier['name'] ?? ''],
            ['Identificación transportista', $carrier['identification_number'] ?? ''],
            ['Fecha inicio transporte', $shipping['start_date'] ?? ''],
            ['Fecha fin transporte', $shipping['end_date'] ?? ''],
            ['Placa', $carrier['plate'] ?? ''],
        ]) . '

    ' . $this->recipientsTable($data) . '

    <table style="margin-top: 8px;">
        <tr>
            <td>' . $this->additionalInfoBox($data) . '</td>
        </tr>
    </table>
</div>');
    }

    private function recipientsTable(RideData $data): string
    {
        $recipients = is_array($data->data['recipients'] ?? null) ? $data->data['recipients'] : [];
        $html = '';

        foreach ($recipients as $recipient)
        {
            if (! is_array($recipient))
            {
                continue;
            }

            $html .= $this->informationBox('Destinatario', [
                ['Razón Social / Nombres y Apellidos', $recipient['name'] ?? ''],
                ['Identificación', $recipient['identification_number'] ?? ''],
                ['Dirección destino', $recipient['destination_address'] ?? ''],
                ['Motivo traslado', $recipient['reason'] ?? ''],
                ['Documento sustento', $this->joinValues([$recipient['supporting_document_code'] ?? null, $recipient['supporting_document_number'] ?? null])],
                ['Autorización documento sustento', $recipient['supporting_document_authorization'] ?? ''],
                ['Ruta', $recipient['route'] ?? ''],
            ]);

            $html .= $this->detailsTable($recipient);
        }

        return $html;
    }

    private function detailsTable(array $recipient): string
    {
        $details = is_array($recipient['details'] ?? null) ? $recipient['details'] : [];
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
    <td>' . $this->e($detail['main_code'] ?? $detail['internal_code'] ?? '') . '</td>
    <td>' . $this->e($detail['auxiliary_code'] ?? $detail['additional_code'] ?? '') . '</td>
    <td>' . $this->e($detail['description'] ?? '') . '</td>
    <td class="right">' . $this->e($detail['quantity'] ?? '') . '</td>
    <td>' . $this->e($additional[0] ?? '') . '</td>
    <td>' . $this->e($additional[1] ?? '') . '</td>
    <td>' . $this->e($additional[2] ?? '') . '</td>
</tr>';
        }

        return '
<table class="items" style="margin-top: 8px;">
    <tr>
        <th>Cód. Interno</th>
        <th>Cód. Adicional</th>
        <th>Descripción</th>
        <th>Cantidad</th>
        <th>Detalle Adicional</th>
        <th>Detalle Adicional</th>
        <th>Detalle Adicional</th>
    </tr>
    ' . $rows . '
</table>';
    }
}
