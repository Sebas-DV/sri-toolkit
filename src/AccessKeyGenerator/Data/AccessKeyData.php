<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator\Data;

use DateTimeImmutable;
use MTZ\Toolkit\AccessKeyGenerator\Enums\DocumentType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\EmissionType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\Environment;
use MTZ\Toolkit\AccessKeyGenerator\Exceptions\AccessKeyException;
use MTZ\Toolkit\AccessKeyGenerator\Support\NumericString;
use Random\RandomException;
use Throwable;

/**
 * Immutable value object holding all fields required to compose an SRI access key.
 *
 * Use the static {@see AccessKeyData::make()} factory to create instances with
 * automatic validation and formatting.
 */
final readonly class AccessKeyData
{
    /**
     * Private constructor to prevent direct instantiation. Use the static make() method for creating instances with proper validation and formatting.
     *
     * @param DateTimeImmutable $emissionDate
     * @param DocumentType $documentType
     * @param string $ruc
     * @param Environment $environment
     * @param string $establishmentCode
     * @param string $emissionPointCode
     * @param string $sequential
     * @param string $numericCode
     * @param EmissionType $emissionType
     */
    private function __construct(
        public DateTimeImmutable $emissionDate,
        public DocumentType $documentType,
        public string $ruc,
        public Environment $environment,
        public string $establishmentCode,
        public string $emissionPointCode,
        public string $sequential,
        public string $numericCode,
        public EmissionType $emissionType,
    ) {
    }

    /**
     * Factory method to create an instance of AccessKeyData with proper validation and formatting.
     *
     * @param string $emissionDate
     * @param DocumentType $documentType
     * @param string $ruc
     * @param Environment $environment
     * @param string|int $sequential
     * @param string|int|null $numericCode
     * @param string|int $establishmentCode
     * @param string|int $emissionPointCode
     * @param EmissionType $emissionType
     * @return AccessKeyData
     * @throws RandomException
     */
    public static function make(
        string $emissionDate,
        DocumentType $documentType,
        string $ruc,
        Environment $environment,
        string|int $sequential,
        string|int|null $numericCode = null,
        string|int $establishmentCode = '001',
        string|int $emissionPointCode = '001',
        EmissionType $emissionType = EmissionType::Normal,
    ): AccessKeyData {
        return new self(
            emissionDate: self::parseDate($emissionDate),
            documentType: $documentType,
            ruc: NumericString::fixed($ruc, 13, 'ruc'),
            environment: $environment,
            establishmentCode: NumericString::fixed($establishmentCode, 3, 'establishment_code'),
            emissionPointCode: NumericString::fixed($emissionPointCode, 3, 'emission_point_code'),
            sequential: NumericString::padded($sequential, 9, 'sequential'),
            numericCode: $numericCode === null ? self::generateNumericCode() : NumericString::fixed($numericCode, 8, 'numeric_code'),
            emissionType: $emissionType,
        );
    }

    /**
     * Generates the base 49-digit access key string (without the check digit).
     *
     * @return string The 49-digit base access key.
     */
    public function toAccessKeyBase(): string
    {
        return $this->emissionDate->format('dmY')
            . $this->documentType->value
            . $this->ruc
            . $this->environment->value
            . $this->establishmentCode
            . $this->emissionPointCode
            . $this->sequential
            . $this->numericCode
            . $this->emissionType->value;
    }

    /**
     * Parses a date string into a DateTimeImmutable object.
     *
     * @param string $date The date string to parse.
     * @return DateTimeImmutable The parsed date.
     * @throws AccessKeyException When the date string is invalid.
     */
    private static function parseDate(string $date): DateTimeImmutable
    {
        try
        {
            return new DateTimeImmutable($date);
        } catch (Throwable)
        {
            throw AccessKeyException::invalidDate($date);
        }
    }

    /**
     * Generates a random 8-digit numeric code used as the unique document identifier.
     *
     * @return string An 8-digit zero-padded numeric string.
     * @throws RandomException When the random number generator fails.
     */
    public static function generateNumericCode(): string
    {
        return str_pad(
            (string) random_int(0, 99999999),
            8,
            '0',
            STR_PAD_LEFT,
        );
    }
}
