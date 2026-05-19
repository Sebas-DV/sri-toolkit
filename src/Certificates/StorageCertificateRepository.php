<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Certificates;

use DateTimeImmutable;
use Exception;
use JsonException;
use MTZ\Toolkit\Certificates\Data\StoredCertificate;
use MTZ\Toolkit\Certificates\Exceptions\CertificateStorageException;
use MTZ\Toolkit\Security\Contracts\StringEncrypterInterface;
use MTZ\Toolkit\Storage\Contracts\DocumentStorageInterface;
use MTZ\Toolkit\Storage\Support\PathNormalizer;

final readonly class StorageCertificateRepository
{
    public function __construct(
        private DocumentStorageInterface $storage,
        private StringEncrypterInterface $encrypter,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function store(
        string             $ownerKey,
        string             $certificateContents,
        string             $password,
        ?string            $alias = null,
        ?DateTimeImmutable $expiresAt = null,
    ): StoredCertificate {
        $ownerKey = PathNormalizer::segment($ownerKey);

        $certificatePath = $this->certificatePath($ownerKey);
        $metadataPath = $this->metadataPath($ownerKey);

        $storedCertificate = new StoredCertificate(
            ownerKey: $ownerKey,
            path: $certificatePath,
            encryptPassword: $this->encrypter->encrypt($password),
            alias: $alias,
            expiresAt: $expiresAt,
        );

        $metadata = json_encode($storedCertificate->toArray(), JSON_THROW_ON_ERROR);

        $this->storage->put($certificatePath, $certificateContents);
        $this->storage->put($metadataPath, $metadata);

        return $storedCertificate;
    }

    /**
     * @throws Exception
     */
    public function get(string $ownerKey): StoredCertificate
    {
        $ownerKey = PathNormalizer::segment($ownerKey);
        $metadataPath = $this->metadataPath($ownerKey);

        if (! $this->storage->exists($metadataPath))
        {
            throw CertificateStorageException::notFound($ownerKey);
        }

        $metadata = $this->storage->get($metadataPath);

        try
        {
            $data = json_decode($metadata, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException)
        {
            throw CertificateStorageException::notFound($ownerKey);
        }

        if (! is_array($data))
        {
            throw CertificateStorageException::notFound($ownerKey);
        }

        return StoredCertificate::fromArray($data);
    }

    public function delete(string $ownerKey): void
    {
        $ownerKey = PathNormalizer::segment($ownerKey);

        $this->storage->delete($this->certificatePath($ownerKey));
        $this->storage->delete($this->metadataPath($ownerKey));
    }

    private function certificatePath(string $ownerKey): string
    {
        return "sri/certificates/$ownerKey/certificate.p12";
    }

    private function metadataPath(string $ownerKey): string
    {
        return "sri/certificates/$ownerKey/metadata.json";
    }
}
