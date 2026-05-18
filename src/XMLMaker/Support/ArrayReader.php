<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Support;

use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;

final readonly class ArrayReader
{
    public function __construct(
        private array $data,
    ) {
    }

    public function string(string $key): string
    {
        if (! isset($this->data[$key]))
        {
            throw InvalidXmlDataException::missingField($key);
        }

        return (string) $this->data[$key];
    }

    public function nullableString(string $key): ?string
    {
        if (! isset($this->data[$key]) || $this->data[$key] === '')
        {
            return null;
        }

        return (string) $this->data[$key];
    }

    public function array(string $key): array
    {
        if (! isset($this->data[$key]))
        {
            throw InvalidXmlDataException::missingField($key);
        }

        if (! is_array($this->data[$key]))
        {
            throw InvalidXmlDataException::invalidArray($key);
        }

        return $this->data[$key];
    }

    public function nullableArray(string $key): array
    {
        if (! isset($this->data[$key]))
        {
            return [];
        }

        if (! is_array($this->data[$key]))
        {
            throw InvalidXmlDataException::invalidArray($key);
        }

        return $this->data[$key];
    }
}
