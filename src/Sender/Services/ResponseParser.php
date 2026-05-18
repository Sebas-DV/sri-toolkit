<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Services;

use MTZ\Toolkit\Sender\Data\AuthorizedDocument;
use MTZ\Toolkit\Sender\Data\Message;
use MTZ\Toolkit\Sender\Enums\AuthorizationStatus;
use MTZ\Toolkit\Sender\Enums\ReceptionStatus;
use Traversable;

final class ResponseParser
{
    public function receptionStatus(object $response): ?ReceptionStatus
    {
        $status = $response->RespuestaRecepcionComprobante->estado ?? null;

        return is_string($status)
            ? ReceptionStatus::tryFrom($status)
            : null;
    }

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
            $messages[] = $this->messageFromSoapObject($message);
        }

        return $messages;
    }

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
                    $messages[] = $this->messageFromSoapObject($item);
                }

                continue;
            }

            $messages[] = $this->messageFromSoapObject($message);
        }

        return $messages;
    }

    public function authorizedDocument(object $response): ?AuthorizedDocument
    {
        $authorization = $this->authorizationNode($response);

        if ($authorization === null)
        {
            return null;
        }

        return new AuthorizedDocument(
            accessKey: $authorization->numeroAutorizacion ?? null,
            xml: $authorization->comprobante ?? null,
            authorizationDate: $authorization->fechaAutorizacion ?? null,
        );
    }

    public function isReceptionSuccessful(object $response): bool
    {
        return $this->receptionStatus($response) === ReceptionStatus::Received;
    }

    public function isAuthorizationSuccessful(object $response): bool
    {
        return $this->authorizationStatus($response) === AuthorizationStatus::Authorized;
    }

    private function authorizationNode(object $response): ?object
    {
        $authorization = $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion ?? null;

        if (is_array($authorization))
        {
            return $authorization[0] ?? null;
        }

        return is_object($authorization) ? $authorization : null;
    }

    private function messageFromSoapObject(object $message): Message
    {
        return new Message(
            type: (string) ($message->tipo ?? 'ERROR'),
            code: (string) ($message->identificador ?? '0'),
            message: (string) ($message->mensaje ?? 'No message, an error occurred.'),
            additionalInformation: (string) ($message->informacionAdicional ?? ''),
        );
    }

    private function normalizeIterable(mixed $value): array
    {
        if (is_array($value))
        {
            return $value;
        }

        if ($value instanceof Traversable)
        {
            return iterator_to_array($value);
        }

        return [$value];
    }
}
