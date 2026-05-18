<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Data;

/**
 * Holds the data of an authorized electronic document returned by the SRI.
 *
 * Includes the access key, the XML content of the authorized document,
 * and the authorization date.
 */
final readonly class AuthorizedDocument
{
    /**
     * @param string|null $accessKey The SRI authorization number.
     * @param string|null $xml The XML content of the authorized document.
     * @param string|null $authorizationDate The date and time the document was authorized.
     */
    public function __construct(
        public ?string $accessKey,
        public ?string $xml,
        public ?string $authorizationDate,
    ) {
    }

    /**
     * Converts the authorized document to an array representation.
     *
     * @return array<string, string|null> The document data as an associative array.
     */
    public function toArray(): array
    {
        return [
            'access_key' => $this->accessKey,
            'xml' => $this->xml,
            'authorization_date' => $this->authorizationDate,
        ];
    }
}
