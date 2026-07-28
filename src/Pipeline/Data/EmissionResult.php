<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Pipeline\Data;

use MTZ\Toolkit\RideGenerator\Data\GeneratedRidePdf;
use MTZ\Toolkit\Sender\Data\SendResult;

/**
 * Outcome of a document pipeline run.
 */
final readonly class EmissionResult
{
    /**
     * @param bool $success Whether the pipeline completed successfully (send authorized, or no send configured).
     * @param string $accessKey The document access key.
     * @param string $generatedXml The generated (unsigned) XML.
     * @param string|null $signedXml The signed XML, when a signer ran.
     * @param SendResult|null $send The send result, when a sender ran.
     * @param GeneratedRidePdf|null $ride The generated RIDE PDF, when produced.
     * @param array<string, string> $storedPaths Storage paths keyed by artifact name.
     * @param list<string> $schemaErrors XSD validation errors, when validation failed.
     * @param string|null $error Human-readable failure reason, when unsuccessful.
     */
    public function __construct(
        public bool $success,
        public string $accessKey,
        public string $generatedXml,
        public ?string $signedXml = null,
        public ?SendResult $send = null,
        public ?GeneratedRidePdf $ride = null,
        public array $storedPaths = [],
        public array $schemaErrors = [],
        public ?string $error = null,
    ) {
    }

    /**
     * Builds a failure result for XML that did not pass schema validation.
     *
     * @param string $accessKey The document access key.
     * @param string $generatedXml The generated XML that failed validation.
     * @param list<string> $schemaErrors The schema validation errors.
     * @param array<string, string> $storedPaths Any artifacts stored before the failure.
     * @return self
     */
    public static function schemaFailure(
        string $accessKey,
        string $generatedXml,
        array $schemaErrors,
        array $storedPaths = [],
    ): self {
        return new self(
            success: false,
            accessKey: $accessKey,
            generatedXml: $generatedXml,
            storedPaths: $storedPaths,
            schemaErrors: $schemaErrors,
            error: 'Generated XML failed XSD schema validation.',
        );
    }
}
