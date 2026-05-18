<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

use MTZ\Toolkit\Sender\Enums\AuthorizationStatus;

/**
 * Represents the result of an SRI authorization request.
 *
 * Contains the success state, authorization status, the authorized document
 * (on success), any error messages, attempt count, and the raw SOAP response.
 */
final readonly class AuthorizationResult
{
    /**
     * @param bool $success Whether the authorization was successful.
     * @param AuthorizationStatus|null $status The authorization status returned by the SRI.
     * @param AuthorizedDocument|null $authorizedDocument The authorized document data, if successful.
     * @param array<int, Message> $messages Messages returned by the SRI web service.
     * @param int $attempts Number of attempts made to authorize.
     * @param string|null $error Error message if the authorization failed.
     * @param object|null $rawResponse The raw SOAP response object.
     */
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

    /**
     * Creates a successful authorization result.
     *
     * @param AuthorizationStatus|null $status The authorization status.
     * @param AuthorizedDocument|null $authorizedDocument The authorized document data.
     * @param array<int, Message> $messages Messages from the SRI.
     * @param int $attempts Number of attempts made.
     * @param object|null $rawResponse The raw SOAP response.
     * @return self
     */
    public static function success(
        ?AuthorizationStatus $status,
        ?AuthorizedDocument $authorizedDocument = null,
        array $messages = [],
        int $attempts = 1,
        ?object $rawResponse = null,
    ): self {
        return new self(true, $status, $authorizedDocument, $messages, $attempts, null, $rawResponse);
    }

    /**
     * Creates a failed authorization result.
     *
     * @param AuthorizationStatus|null $status The authorization status, if available.
     * @param string $error The error message describing the failure.
     * @param array<int, Message> $messages Messages from the SRI, if available.
     * @param int $attempts Number of attempts made.
     * @param object|null $rawResponse The raw SOAP response, if available.
     * @return self
     */
    public static function failure(
        ?AuthorizationStatus $status,
        string $error,
        array $messages = [],
        int $attempts = 0,
        ?object $rawResponse = null,
    ): self {
        return new self(false, $status, null, $messages, $attempts, $error, $rawResponse);
    }

    /**
     * Converts the authorization result to an array representation.
     *
     * @return array<string, mixed> The result as an associative array.
     */
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
