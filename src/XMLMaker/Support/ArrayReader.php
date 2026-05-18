<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Support;

use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;

/**
 * Immutable, type-safe wrapper around an associative array for reading XML data fields.
 *
 * Provides methods to safely extract string and array values with appropriate
 * validation and nullability handling.
 */
final readonly class ArrayReader
{
    /**
     * @param array $data The underlying associative array.
     */
    public function __construct(
        private array $data,
    ) {
    }

    /**
     * Reads a required string field, throwing if missing.
     *
     * @param string $key The array key to read.
     * @return string The value cast to string.
     * @throws InvalidXmlDataException When the key is not set.
     */
    public function string(string $key): string
    {
        if (! isset($this->data[$key]))
        {
            throw InvalidXmlDataException::missingField($key);
        }

        return (string) $this->data[$key];
    }

    /**
     * Reads an optional string field, returning null when not set or empty.
     *
     * @param string $key The array key to read.
     * @return string|null The value cast to string, or null if absent/empty.
     */
    public function nullableString(string $key): ?string
    {
        if (! isset($this->data[$key]) || $this->data[$key] === '')
        {
            return null;
        }

        return (string) $this->data[$key];
    }

    /**
     * Reads a required array field, throwing if missing or not an array.
     *
     * @param string $key The array key to read.
     * @return array The array value.
     * @throws InvalidXmlDataException When the key is missing or the value is not an array.
     */
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

    /**
     * Reads an optional array field, returning an empty array when not set.
     *
     * @param string $key The array key to read.
     * @return array The array value, or an empty array if absent.
     * @throws InvalidXmlDataException When the value exists but is not an array.
     */
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
