<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Builders;

use DOMElement;
use DOMException;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use MTZ\Toolkit\XMLMaker\Support\ArrayReader;

/**
 * Builds SRI Delivery Guide (Guía de Remisión) XML documents.
 */
final class DeliveryGuideXmlBuilder extends AbstractXmlDocumentBuilder
{
    /**
     * Appends delivery-guide-specific information to the root element.
     *
     * @param DOMElement $root The root element of the XML document.
     * @param XmlGenerationData $data The generation data including delivery guide payload.
     * @throws DOMException When a required DOM element cannot be created.
     */
    protected function appendDocumentInformation(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);
        $company = new ArrayReader($reader->array('company'));
        $carrier = new ArrayReader($reader->array('carrier'));
        $shipping = new ArrayReader($reader->array('shipping'));

        $deliveryGuideInformation = $this->dom->child($root, 'infoGuiaRemision');

        $this->dom->append($deliveryGuideInformation, 'dirEstablecimiento', $reader->nullableString('establishment_address'));
        $this->dom->append($deliveryGuideInformation, 'dirPartida', $shipping->string('start_address'));

        $this->dom->append($deliveryGuideInformation, 'razonSocialTransportista', $carrier->string('name'));
        $this->dom->append($deliveryGuideInformation, 'tipoIdentificacionTransportista', $carrier->string('identification_type'));
        $this->dom->append($deliveryGuideInformation, 'rucTransportista', $carrier->string('identification_number'));
        $this->dom->append($deliveryGuideInformation, 'rise', $reader->nullableString('rise'));

        $this->dom->append($deliveryGuideInformation, 'obligadoContabilidad', $reader->nullableString('requires_accounting'));
        $this->dom->append(
            $deliveryGuideInformation,
            'contribuyenteEspecial',
            $reader->nullableString('special_taxpayer_number') ?? $company->nullableString('special_taxpayer_number'),
        );
        $this->dom->append($deliveryGuideInformation, 'fechaIniTransporte', $shipping->string('start_date'));
        $this->dom->append($deliveryGuideInformation, 'fechaFinTransporte', $shipping->string('end_date'));
        $this->dom->append($deliveryGuideInformation, 'placa', $carrier->string('plate'));

        $this->appendRecipients($root, $data);
    }

    /**
     * Appends the recipients (destinatarios) section to the root element.
     *
     * @param DOMElement $root The root element to append recipients to.
     * @param XmlGenerationData $data The generation data containing recipient entries.
     * @throws InvalidXmlDataException When recipients are empty.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendRecipients(DOMElement $root, XmlGenerationData $data): void
    {
        $reader = new ArrayReader($data->data);
        $recipients = $reader->array('recipients');

        if ($recipients === [])
        {
            throw InvalidXmlDataException::emptyItems('recipients');
        }

        $recipientsElement = $this->dom->child($root, 'destinatarios');

        foreach ($recipients as $recipient)
        {
            $recipientReader = new ArrayReader($recipient);

            $recipientElement = $this->dom->child($recipientsElement, 'destinatario');

            $this->dom->append($recipientElement, 'identificacionDestinatario', $recipientReader->string('identification_number'));
            $this->dom->append($recipientElement, 'razonSocialDestinatario', $recipientReader->string('name'));
            $this->dom->append($recipientElement, 'dirDestinatario', $recipientReader->string('destination_address'));
            $this->dom->append($recipientElement, 'motivoTraslado', $recipientReader->string('reason'));
            $this->dom->append($recipientElement, 'docAduaneroUnico', $recipientReader->nullableString('customs_document'));
            $this->dom->append($recipientElement, 'codEstabDestino', $recipientReader->nullableString('destination_establishment_code'));
            $this->dom->append($recipientElement, 'ruta', $recipientReader->nullableString('route'));

            $this->dom->append($recipientElement, 'codDocSustento', $recipientReader->nullableString('supporting_document_code'));
            $this->dom->append($recipientElement, 'numDocSustento', $recipientReader->nullableString('supporting_document_number'));
            $this->dom->append($recipientElement, 'numAutDocSustento', $recipientReader->nullableString('supporting_document_authorization'));
            $this->dom->append($recipientElement, 'fechaEmisionDocSustento', $recipientReader->nullableString('supporting_document_emission_date'));

            $this->appendRecipientDetails($recipientElement, $recipientReader->array('details'));
        }
    }

    /**
     * Appends detail lines (detalles) to a recipient element.
     *
     * @param DOMElement $recipientElement The recipient element to append details to.
     * @param array $details Array of detail entries, each with main_code, auxiliary_code, description, and quantity.
     * @throws InvalidXmlDataException When details are empty.
     * @throws DOMException When a required DOM element cannot be created.
     */
    private function appendRecipientDetails(DOMElement $recipientElement, array $details): void
    {
        if ($details === [])
        {
            throw InvalidXmlDataException::emptyItems('recipients.*.details');
        }

        $detailsElement = $this->dom->child($recipientElement, 'detalles');

        foreach ($details as $detail)
        {
            $detailReader = new ArrayReader($detail);

            $detailElement = $this->dom->child($detailsElement, 'detalle');

            $this->dom->append($detailElement, 'codigoInterno', $detailReader->nullableString('main_code'));
            $this->dom->append($detailElement, 'codigoAdicional', $detailReader->nullableString('auxiliary_code'));
            $this->dom->append($detailElement, 'descripcion', $detailReader->string('description'));
            $this->dom->append($detailElement, 'cantidad', $detailReader->string('quantity'));

            $this->appendAdditionalDetailFields($detailElement, $detailReader->nullableArray('additional_info'));
        }
    }
}
