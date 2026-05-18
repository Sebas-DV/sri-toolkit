<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

final readonly class AuthorizedDocument
{
    public function __construct(
        public ?string $accessKey,
        public ?string $xml,
        public ?string $authorizationDate,
    ) {
    }

    public function toArray(): array
    {
        return [
            'access_key' => $this->accessKey,
            'xml' => $this->xml,
            'authorization_date' => $this->authorizationDate,
        ];
    }
}
