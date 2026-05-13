<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

final readonly class SendResult
{
    public function __construct(
        public bool $success,
        public ?ReceptionResult $receptionResult = null,
        public ?AuthorizationResult $authorizationResult = null,
        public ?string $error = null,
    )
    {
    }

    public static function success(
        ReceptionResult  $receptionStatus,
        AuthorizationResult $authorizationResult,
    ): self
    {
        return new self(true, $receptionStatus, $authorizationResult);
    }

    public static function failure(
        string $error,
        ?ReceptionResult $receptionResult = null,
        ?AuthorizationResult $authorizationResult = null,
    ): self
    {
        return new self(false, $receptionResult, $authorizationResult, $error);
    }

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