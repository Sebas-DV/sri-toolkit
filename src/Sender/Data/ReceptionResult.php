<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

use MTZ\Toolkit\Sender\Enums\ReceptionStatus;

final readonly class ReceptionResult
{
    public function __construct(
        public bool $success,
        public ?ReceptionStatus $status,
        public array $messages = [],
        public ?string $error = null,
        public ?object $rawResponse = null,
    ) {
    }

    public static function success(?ReceptionStatus $status, array $messages = [], ?object $rawResponse = null): self
    {
        return new self(true, $status, $messages, null, $rawResponse);
    }

    public static function failure(?ReceptionStatus $status, ?string $error = null, array $messages = [], ?object $rawResponse = null): self
    {
        return new self(false, $status, $messages, $error, $rawResponse);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status?->value,
            'messages' => $this->messages,
            'error' => $this->error,
        ];
    }
}
