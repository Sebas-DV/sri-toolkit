<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Certificates\Data;

use DateTimeImmutable;
use Exception;

final readonly class StoredCertificate
{
    public function __construct(
        public string $ownerKey,
        public string $path,
        public string $encryptPassword,
        public ?string $alias = null,
        public ?DateTimeImmutable $expiresAt = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'ownerKey' => $this->ownerKey,
            'path' => $this->path,
            'encrypted_password' => $this->encryptPassword,
            'alias' => $this->alias,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        $expiresAt = null;

        if (isset($data['expires_at']) && is_string($data['expires_at']) && $data['expires_at'] !== '')
        {
            $expiresAt = new DateTimeImmutable($data['expires_at']);
        }

        return new self(
            ownerKey: (string) ($data['ownerKey'] ?? ''),
            path: (string) ($data['path'] ?? ''),
            encryptPassword: (string) ($data['encrypted_password'] ?? ''),
            alias: isset($data['alias']) && is_string($data['alias']) ? $data['alias'] : null,
            expiresAt: $expiresAt,
        );
    }
}
