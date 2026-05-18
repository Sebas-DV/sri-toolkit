<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

use MTZ\Toolkit\Sender\Enums\ReceptionStatus;

/**
 * Represents the result of an SRI reception request.
 *
 * Contains the success state, reception status, messages returned by the SRI,
 * and the raw SOAP response.
 */
final readonly class ReceptionResult
{
    /**
     * @param bool $success Whether the reception was successful (document received).
     * @param ReceptionStatus|null $status The reception status returned by the SRI.
     * @param array<int, Message> $messages Messages returned by the SRI web service.
     * @param string|null $error Error message if the reception failed.
     * @param object|null $rawResponse The raw SOAP response object.
     */
    public function __construct(
        public bool $success,
        public ?ReceptionStatus $status,
        public array $messages = [],
        public ?string $error = null,
        public ?object $rawResponse = null,
    ) {
    }

    /**
     * Creates a successful reception result.
     *
     * @param ReceptionStatus|null $status The reception status.
     * @param array<int, Message> $messages Messages from the SRI.
     * @param object|null $rawResponse The raw SOAP response.
     * @return self
     */
    public static function success(?ReceptionStatus $status, array $messages = [], ?object $rawResponse = null): self
    {
        return new self(true, $status, $messages, null, $rawResponse);
    }

    /**
     * Creates a failed reception result.
     *
     * @param ReceptionStatus|null $status The reception status, if available.
     * @param string|null $error The error message describing the failure.
     * @param array<int, Message> $messages Messages from the SRI, if available.
     * @param object|null $rawResponse The raw SOAP response, if available.
     * @return self
     */
    public static function failure(?ReceptionStatus $status, ?string $error = null, array $messages = [], ?object $rawResponse = null): self
    {
        return new self(false, $status, $messages, $error, $rawResponse);
    }

    /**
     * Converts the reception result to an array representation.
     *
     * @return array<string, mixed> The result as an associative array.
     */
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
