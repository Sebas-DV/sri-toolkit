<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

/**
 * Represents a single message returned by the SRI web service.
 *
 * Messages include a type (e.g., INFO, WARN, ERROR), a code, a human-readable message,
 * and optional additional information.
 */
final readonly class Message
{
    /**
     * @param string $type The message type (e.g., INFO, WARN, ERROR).
     * @param string $code The message identifier code.
     * @param string $message The human-readable message text.
     * @param string $additionalInformation Additional details or context.
     */
    public function __construct(
        public string $type,
        public string $code,
        public string $message,
        public string $additionalInformation,
    ) {
    }

    /**
     * Returns the message as a formatted string.
     *
     * @return string The formatted message string.
     */
    public function toString(): string
    {
        return trim("$this->type $this->code: $this->message $this->additionalInformation");
    }

    /**
     * Converts the message to an array representation.
     *
     * @return array<string, string> The message data as an associative array.
     */
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
