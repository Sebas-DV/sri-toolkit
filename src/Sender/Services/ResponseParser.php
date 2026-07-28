<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Services;

use MTZ\Toolkit\Sender\Data\AuthorizationResult;
use MTZ\Toolkit\Sender\Data\AuthorizedDocument;
use MTZ\Toolkit\Sender\Data\ConsultationResult;
use MTZ\Toolkit\Sender\Data\Message;
use MTZ\Toolkit\Sender\Enums\AuthorizationStatus;
use MTZ\Toolkit\Sender\Enums\ConsultationStatus;
use MTZ\Toolkit\Sender\Enums\ReceptionStatus;
use Traversable;

/**
 * Parses SOAP responses from the SRI web service into domain objects.
 *
 * Provides methods to extract reception status, authorization status, messages,
 * and authorized document details from raw SOAP response objects. Handles
 * normalization of various SOAP response structures.
 */
final class ResponseParser
{
    /**
     * Parses the SOAP response from the SRI web service and extracts relevant information such as reception status,
     * authorization status, messages, and authorized document details.
     *
     * @param object $response
     * @return ReceptionStatus|null
     */
    public function receptionStatus(object $response): ?ReceptionStatus
    {
        $status = $response->RespuestaRecepcionComprobante->estado ?? null;

        return is_string($status)
            ? ReceptionStatus::tryFrom($status)
            : null;
    }

    /**
     * Parses the SOAP response from the SRI web service and extracts relevant information such as authorization status,
     * messages and authorized document details.
     *
     * @param object $response
     * @return AuthorizationStatus|null
     */
    public function authorizationStatus(object $response): ?AuthorizationStatus
    {
        $authorization = $this->authorizationNode($response);

        if ($authorization === null)
        {
            return null;
        }

        $status = $authorization->estado ?? null;

        return is_string($status)
            ? AuthorizationStatus::tryFrom($status)
            : null;
    }

    /**
     * Parses the SOAP response from the SRI web service and extracts relevant information such as messages.
     *
     * @param object $response
     * @return array<int, Message>
     */
    public function receptionMessage(object $response): array
    {
        $messages = [];

        $voucher = $response->RespuestaRecepcionComprobante->comprobantes->comprobante ?? null;

        if ($voucher === null || ! isset($voucher->mensajes))
        {
            return [];
        }

        foreach ($this->normalizeIterable($voucher->mensajes) as $message)
        {
            if (! is_object($message))
            {
                continue;
            }

            $messages[] = $this->messageFromSoapObject($message);
        }

        return $messages;
    }

    /**
     * Parses the SOAP response from the SRI web service and extracts relevant information such as messages.
     *
     * @param object $response
     * @return array<int, Message>
     */
    public function authorizationMessages(object $response): array
    {
        $authorization = $this->authorizationNode($response);

        if ($authorization === null || ! isset($authorization->mensajes))
        {
            return [];
        }

        $messages = [];

        foreach ($this->normalizeIterable($authorization->mensajes) as $message)
        {
            if (is_array($message))
            {
                foreach ($message as $item)
                {
                    if (! is_object($item))
                    {
                        continue;
                    }

                    $messages[] = $this->messageFromSoapObject($item);
                }

                continue;
            }

            if (! is_object($message))
            {
                continue;
            }

            $messages[] = $this->messageFromSoapObject($message);
        }

        return $messages;
    }

    /**
     * Parses the SOAP response from the SRI web service and extracts relevant information such as authorized document details.
     *
     * @param object $response
     * @return AuthorizedDocument|null
     */
    public function authorizedDocument(object $response): ?AuthorizedDocument
    {
        $authorization = $this->authorizationNode($response);

        if ($authorization === null)
        {
            return null;
        }

        $accessKey = $authorization->numeroAutorizacion ?? null;
        $xml = $authorization->comprobante ?? null;
        $authorizationDate = $authorization->fechaAutorizacion ?? null;

        return new AuthorizedDocument(
            accessKey: is_string($accessKey) ? $accessKey : null,
            xml: is_string($xml) ? $xml : null,
            authorizationDate: is_string($authorizationDate) ? $authorizationDate : null,
        );
    }

    /**
     * Parses the SOAP response from the SRI web service and extracts relevant information such as reception status,
     * authorization status, messages, and authorized document details.
     *
     * @param object $response
     * @return bool
     */
    public function isReceptionSuccessful(object $response): bool
    {
        return $this->receptionStatus($response) === ReceptionStatus::Received;
    }


    /**
     * Parses the SOAP response from the SRI web service and extracts relevant information such as authorization status,
     * messages and authorized document details.
     *
     * @param object $response
     * @return bool
     */
    public function isAuthorizationSuccessful(object $response): bool
    {
        return $this->authorizationStatus($response) === AuthorizationStatus::Authorized;
    }

    /**
     * Parses a batch (lote) authorization SOAP response into per-voucher results.
     *
     * @param object $response The raw SOAP response from autorizacionComprobanteLote.
     * @return list<AuthorizationResult>
     */
    public function batchAuthorizations(object $response): array
    {
        $lote = $response->RespuestaAutorizacionLote ?? null;

        if (! is_object($lote))
        {
            return [];
        }

        $nodes = $lote->autorizaciones->autorizacion ?? null;

        if (is_object($nodes))
        {
            $nodes = [$nodes];
        }

        if (! is_array($nodes))
        {
            return [];
        }

        $results = [];

        foreach ($nodes as $node)
        {
            if (! is_object($node))
            {
                continue;
            }

            $status = $this->statusFromNode($node);
            $messages = $this->messagesFromNode($node->mensajes ?? null);

            if ($status === AuthorizationStatus::Authorized)
            {
                $results[] = AuthorizationResult::success(
                    status: $status,
                    authorizedDocument: $this->documentFromNode($node),
                    messages: $messages,
                    attempts: 1,
                );

                continue;
            }

            $results[] = AuthorizationResult::failure(
                status: $status,
                error: $this->messagesToString($messages) ?: 'The voucher was not authorized.',
                messages: $messages,
                attempts: 1,
            );
        }

        return $results;
    }

    /**
     * Reads the authorization status from an autorizacion node.
     *
     * @param object $node
     * @return AuthorizationStatus|null
     */
    private function statusFromNode(object $node): ?AuthorizationStatus
    {
        $status = $node->estado ?? null;

        return is_string($status) ? AuthorizationStatus::tryFrom(trim($status)) : null;
    }

    /**
     * Builds an AuthorizedDocument from an autorizacion node.
     *
     * @param object $node
     * @return AuthorizedDocument
     */
    private function documentFromNode(object $node): AuthorizedDocument
    {
        return new AuthorizedDocument(
            accessKey: $this->stringOrNull($node->numeroAutorizacion ?? null),
            xml: $this->stringOrNull($node->comprobante ?? null),
            authorizationDate: $this->stringOrNull($node->fechaAutorizacion ?? null),
        );
    }

    /**
     * Parses the consultation SOAP response into a ConsultationResult.
     *
     * @param object $response The raw SOAP response from ConsultaComprobante.
     * @return ConsultationResult
     */
    public function consultationResult(object $response): ConsultationResult
    {
        $node = $response->EstadoAutorizacionComprobante ?? null;

        if (! is_object($node))
        {
            return ConsultationResult::failure('Invalid response from WebService SRI');
        }

        $status = $this->consultationStatus($node);
        $messages = $this->messagesFromNode($node->mensajes ?? null);
        $accessKey = $this->stringOrNull($node->claveAcceso ?? null);

        if ($status === null || $status === ConsultationStatus::Rejected)
        {
            return ConsultationResult::failure(
                error: $messages === []
                    ? 'The SRI consultation service rejected the request.'
                    : $this->messagesToString($messages),
                status: $status,
                accessKey: $accessKey,
                messages: $messages,
            );
        }

        return ConsultationResult::success(
            status: $status,
            accessKey: $accessKey,
            documentType: $this->stringOrNull($node->tipoComprobante ?? null),
            issuerRuc: $this->stringOrNull($node->rucEmisor ?? null),
            authorizationDate: $this->stringOrNull($node->fechaAutorizacion ?? null),
            messages: $messages,
        );
    }

    /**
     * Reads the consultation status from the estadoAutorizacion or estadoConsulta tag.
     *
     * @param object $node
     * @return ConsultationStatus|null
     */
    private function consultationStatus(object $node): ?ConsultationStatus
    {
        $status = $node->estadoAutorizacion ?? $node->estadoConsulta ?? null;

        return is_string($status)
            ? ConsultationStatus::tryFrom(trim($status))
            : null;
    }

    /**
     * Parses a mensajes node into a list of messages.
     *
     * @param mixed $mensajes
     * @return list<Message>
     */
    private function messagesFromNode(mixed $mensajes): array
    {
        if ($mensajes === null)
        {
            return [];
        }

        $messages = [];

        foreach ($this->normalizeIterable($mensajes) as $message)
        {
            if (is_array($message))
            {
                foreach ($message as $item)
                {
                    if (is_object($item))
                    {
                        $messages[] = $this->messageFromSoapObject($item);
                    }
                }

                continue;
            }

            if (is_object($message))
            {
                $messages[] = $this->messageFromSoapObject($message);
            }
        }

        return $messages;
    }

    /**
     * Concatenates messages into a single human-readable string.
     *
     * @param array<int, Message> $messages
     * @return string
     */
    private function messagesToString(array $messages): string
    {
        return implode(
            "\n",
            array_map(static fn (Message $message): string => $message->toString(), $messages),
        );
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value))
        {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Helper method to extract the authorization node from the SOAP response, handling cases where it may be an array or a single object.
     *
     * @param object $response
     * @return object|null
     */
    private function authorizationNode(object $response): ?object
    {
        $authorization = $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion ?? null;

        if (is_array($authorization))
        {
            $firstAuthorization = $authorization[0] ?? null;

            return is_object($firstAuthorization) ? $firstAuthorization : null;
        }

        return is_object($authorization) ? $authorization : null;
    }

    /**
     * Helper method to convert a SOAP object into a Message object.
     *
     * @param object $message
     * @return Message
     */
    private function messageFromSoapObject(object $message): Message
    {
        return new Message(
            type: $this->toString($message->tipo ?? 'ERROR'),
            code: $this->toString($message->identificador ?? '0'),
            message: $this->toString($message->mensaje ?? 'No message, an error occurred.'),
            additionalInformation: $this->toString($message->informacionAdicional ?? ''),
        );
    }

    /**
     * Helper method to normalize different iterable structures (arrays, Traversable objects, or single objects) into a consistent array format.
     *
     * @return array<int, mixed>
     */
    private function normalizeIterable(mixed $value): array
    {
        if (is_object($value) && isset($value->mensaje))
        {
            $value = $value->mensaje;
        }

        if (is_array($value))
        {
            return array_values($value);
        }

        if ($value instanceof Traversable)
        {
            return array_values(iterator_to_array($value));
        }

        return [$value];
    }

    /**
     * Helper method to safely convert various types of values into strings, providing a default value when conversion is not possible.
     *
     * @param mixed $value
     * @param string $default
     * @return string
     */
    private function toString(mixed $value, string $default = ''): string
    {
        if ($value === null)
        {
            return $default;
        }

        if (is_scalar($value))
        {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, '__toString'))
        {
            return (string) $value;
        }

        if (is_object($value) && isset($value->mensaje) && is_scalar($value->mensaje))
        {
            return (string) $value->mensaje;
        }

        if (is_object($value) && isset($value->identificador) && is_scalar($value->identificador))
        {
            return (string) $value->identificador;
        }

        return $default;
    }
}
