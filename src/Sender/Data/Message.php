<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

final readonly class Message
{
    public function __construct(
        public string $type,
        public string $code,
        public string $message,
        public string $additionalInformation,
    ) {
    }

    public function toString(): string
    {
        return trim("$this->type $this->code: $this->message $this->additionalInformation");
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'code' => $this->code,
            'message' => $this->message,
            'additional_info' => $this->additionalInformation,
        ];
    }
}
