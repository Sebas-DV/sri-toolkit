<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

/**
 * Aggregates the results of both reception and authorization steps for a single send operation.
 *
 * Combines the reception and authorization results into one unified response,
 * indicating whether the full send pipeline completed successfully.
 */
final readonly class SendResult
{
    /**
     * @param bool $success Whether the entire send operation was successful.
     * @param ReceptionResult|null $receptionResult The result of the reception step.
     * @param AuthorizationResult|null $authorizationResult The result of the authorization step.
     * @param string|null $error Error message if the send operation failed.
     */
    public function __construct(
        public bool $success,
        public ?ReceptionResult $receptionResult = null,
        public ?AuthorizationResult $authorizationResult = null,
        public ?string $error = null,
    ) {
    }

    /**
     * Creates a successful send result with both reception and authorization results.
     *
     * @param ReceptionResult $receptionStatus The reception result.
     * @param AuthorizationResult $authorizationResult The authorization result.
     * @return self
     */
    public static function success(
        ReceptionResult  $receptionStatus,
        AuthorizationResult $authorizationResult,
    ): self {
        return new self(true, $receptionStatus, $authorizationResult);
    }

    /**
     * Creates a failed send result with an error message and optional partial results.
     *
     * @param string $error The error message describing the failure.
     * @param ReceptionResult|null $receptionResult The reception result, if available.
     * @param AuthorizationResult|null $authorizationResult The authorization result, if available.
     * @return self
     */
    public static function failure(
        string $error,
        ?ReceptionResult $receptionResult = null,
        ?AuthorizationResult $authorizationResult = null,
    ): self {
        return new self(false, $receptionResult, $authorizationResult, $error);
    }

    /**
     * Converts the send result to an array representation.
     *
     * @return array<string, mixed> The result as an associative array.
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
