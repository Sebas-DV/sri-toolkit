<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

use MTZ\Toolkit\Sender\Enums\ConsultationStatus;

/**
 * Result of querying a document's current state via the SRI consultation service.
 */
final readonly class ConsultationResult
{
    /**
     * @param bool $success Whether the query returned a recognized authorization state.
     * @param ConsultationStatus|null $status The reported state.
     * @param string|null $accessKey The queried access key echoed back.
     * @param string|null $documentType The document type description (tipoComprobante).
     * @param string|null $issuerRuc The issuer RUC (rucEmisor).
     * @param string|null $authorizationDate The authorization date, when authorized.
     * @param list<Message> $messages Any messages returned by the service.
     * @param string|null $error Human-readable failure reason, when unsuccessful.
     */
    public function __construct(
        public bool $success,
        public ?ConsultationStatus $status = null,
        public ?string $accessKey = null,
        public ?string $documentType = null,
        public ?string $issuerRuc = null,
        public ?string $authorizationDate = null,
        public array $messages = [],
        public ?string $error = null,
    ) {
    }

    /**
     * @param list<Message> $messages
     */
    public static function success(
        ConsultationStatus $status,
        ?string $accessKey = null,
        ?string $documentType = null,
        ?string $issuerRuc = null,
        ?string $authorizationDate = null,
        array $messages = [],
    ): self {
        return new self(
            success: true,
            status: $status,
            accessKey: $accessKey,
            documentType: $documentType,
            issuerRuc: $issuerRuc,
            authorizationDate: $authorizationDate,
            messages: $messages,
        );
    }

    /**
     * @param list<Message> $messages
     */
    public static function failure(
        string $error,
        ?ConsultationStatus $status = null,
        ?string $accessKey = null,
        array $messages = [],
    ): self {
        return new self(
            success: false,
            status: $status,
            accessKey: $accessKey,
            messages: $messages,
            error: $error,
        );
    }

    public function isAuthorized(): bool
    {
        return $this->status === ConsultationStatus::Authorized;
    }

    public function isAnnulled(): bool
    {
        return $this->status === ConsultationStatus::Annulled;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status?->value,
            'access_key' => $this->accessKey,
            'document_type' => $this->documentType,
            'issuer_ruc' => $this->issuerRuc,
            'authorization_date' => $this->authorizationDate,
            'messages' => array_map(static fn (Message $message): array => $message->toArray(), $this->messages),
            'error' => $this->error,
        ];
    }
}
