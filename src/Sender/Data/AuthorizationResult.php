<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

use MTZ\Toolkit\Sender\Enums\AuthorizationStatus;

final readonly class AuthorizationResult
{
    public function __construct(
        public bool $success,
        public ?AuthorizationStatus $status,
        public ?AuthorizedDocument $authorizedDocument = null,
        public array $messages = [],
        public int $attempts = 0,
        public ?string $error = null,
        public ?object $rawResponse = null,
    ) {
    }

    public static function success(
        ?AuthorizationStatus $status,
        ?AuthorizedDocument $authorizedDocument = null,
        array $messages = [],
        int $attempts = 1,
        ?object $rawResponse = null,
    ): self {
        return new self(true, $status, $authorizedDocument, $messages, $attempts, null, $rawResponse);
    }

    public static function failure(
        ?AuthorizationStatus $status,
        string $error,
        array $messages = [],
        int $attempts = 0,
        ?object $rawResponse = null,
    ): self {
        return new self(false, $status, null, $messages, $attempts, $error, $rawResponse);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status?->value,
            'authorized_document' => $this->authorizedDocument?->toArray(),
            'messages' => array_map(
                static fn (Message $message): array => $message->toArray(),
                $this->messages,
            ),
            'attempts' => $this->attempts,
            'error' => $this->error,
        ];
    }
}
