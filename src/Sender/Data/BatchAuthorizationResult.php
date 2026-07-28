<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

/**
 * Result of authorizing a batch (lote), with one authorization per voucher.
 */
final readonly class BatchAuthorizationResult
{
    /**
     * @param bool $success Whether every voucher in the batch was authorized.
     * @param string|null $loteAccessKey The batch access key.
     * @param list<AuthorizationResult> $authorizations Per-voucher authorization results.
     * @param string|null $error Human-readable failure reason, when unsuccessful.
     * @param object|null $rawResponse The raw SOAP response object.
     */
    public function __construct(
        public bool $success,
        public ?string $loteAccessKey = null,
        public array $authorizations = [],
        public ?string $error = null,
        public ?object $rawResponse = null,
    ) {
    }

    /**
     * @param list<AuthorizationResult> $authorizations
     */
    public static function success(string $loteAccessKey, array $authorizations, ?object $rawResponse = null): self
    {
        return new self(true, $loteAccessKey, $authorizations, null, $rawResponse);
    }

    /**
     * @param list<AuthorizationResult> $authorizations
     */
    public static function failure(
        string $error,
        ?string $loteAccessKey = null,
        array $authorizations = [],
        ?object $rawResponse = null,
    ): self {
        return new self(false, $loteAccessKey, $authorizations, $error, $rawResponse);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'lote_access_key' => $this->loteAccessKey,
            'authorizations' => array_map(
                static fn (AuthorizationResult $result): array => $result->toArray(),
                $this->authorizations,
            ),
            'error' => $this->error,
        ];
    }
}
