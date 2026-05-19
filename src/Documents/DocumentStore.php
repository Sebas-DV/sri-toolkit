<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Documents;

use DateTimeImmutable;
use JsonException;
use MTZ\Toolkit\Documents\Enums\DocumentArtifact;
use MTZ\Toolkit\Storage\Contracts\DocumentStorageInterface;
use MTZ\Toolkit\Storage\Support\PathNormalizer;

final readonly class DocumentStore
{
    public function __construct(
        private DocumentStorageInterface $storage,
    ) {
    }

    public function putGeneratedXml(
        string $ownerKey,
        DateTimeImmutable $date,
        string $accessKey,
        string $xml,
    ): string {
        return $this->put($ownerKey, $date, $accessKey, DocumentArtifact::GeneratedXml, $xml);
    }

    public function putSignedXml(
        string $ownerKey,
        DateTimeImmutable $date,
        string $accessKey,
        string $xml,
    ): string {
        return $this->put($ownerKey, $date, $accessKey, DocumentArtifact::SignedXml, $xml);
    }

    public function putAuthorizedXml(
        string $ownerKey,
        DateTimeImmutable $date,
        string $accessKey,
        string $xml,
    ): string {
        return $this->put($ownerKey, $date, $accessKey, DocumentArtifact::AuthorizedXml, $xml);
    }

    /**
     * @throws JsonException
     */
    public function putReceptionResponse(
        string $ownerKey,
        DateTimeImmutable $date,
        string $accessKey,
        array $response,
    ): string {
        return $this->putJson($ownerKey, $date, $accessKey, DocumentArtifact::ReceptionResponse, $response);
    }

    /**
     * @throws JsonException
     */
    public function putAuthorizationResponse(
        string $ownerKey,
        DateTimeImmutable $date,
        string $accessKey,
        array $response,
    ): string {
        return $this->putJson($ownerKey, $date, $accessKey, DocumentArtifact::AuthorizationResponse, $response);
    }

    public function putRidePdf(
        string $ownerKey,
        DateTimeImmutable $date,
        string $accessKey,
        string $pdf,
    ): string {
        return $this->put($ownerKey, $date, $accessKey, DocumentArtifact::RidePdf, $pdf);
    }

    public function get(string $path): string
    {
        return $this->storage->get($path);
    }

    private function put(
        string $ownerKey,
        DateTimeImmutable $date,
        string $accessKey,
        DocumentArtifact $artifact,
        string $contents,
    ): string {
        $path = $this->path($ownerKey, $date, $accessKey, $artifact);

        $this->storage->put($path, $contents);

        return $path;
    }

    /**
     * @throws JsonException
     */
    private function putJson(
        string $ownerKey,
        DateTimeImmutable $date,
        string $accessKey,
        DocumentArtifact $artifact,
        array $payload,
    ): string {
        return $this->put(
            ownerKey: $ownerKey,
            date: $date,
            accessKey: $accessKey,
            artifact: $artifact,
            contents: json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }

    private function path(
        string $ownerKey,
        DateTimeImmutable $date,
        string $accessKey,
        DocumentArtifact $artifact,
    ): string {
        $ownerKey = PathNormalizer::segment($ownerKey);
        $year = $date->format('Y');
        $month = $date->format('m');
        $accessKey = PathNormalizer::segment($accessKey);

        return "sri/documents/$ownerKey/$year/$month/$accessKey/$artifact->value";
    }
}
