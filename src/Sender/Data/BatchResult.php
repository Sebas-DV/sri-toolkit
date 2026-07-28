<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

/**
 * Aggregates the reception and batch-authorization results of a batch send.
 */
final readonly class BatchResult
{
    /**
     * @param bool $success Whether the batch was received and every voucher authorized.
     * @param ReceptionResult|null $receptionResult The reception result of the batch.
     * @param BatchAuthorizationResult|null $authorizationResult The batch authorization result.
     * @param string|null $error Human-readable failure reason, when unsuccessful.
     */
    public function __construct(
        public bool $success,
        public ?ReceptionResult $receptionResult = null,
        public ?BatchAuthorizationResult $authorizationResult = null,
        public ?string $error = null,
    ) {
    }

    public static function success(
        ReceptionResult $receptionResult,
        BatchAuthorizationResult $authorizationResult,
    ): self {
        return new self(true, $receptionResult, $authorizationResult);
    }

    public static function failure(
        string $error,
        ?ReceptionResult $receptionResult = null,
        ?BatchAuthorizationResult $authorizationResult = null,
    ): self {
        return new self(false, $receptionResult, $authorizationResult, $error);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'reception' => $this->receptionResult?->toArray(),
            'authorization' => $this->authorizationResult?->toArray(),
            'error' => $this->error,
        ];
    }
}
