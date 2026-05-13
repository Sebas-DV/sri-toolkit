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

final readonly class AccessKeyData
{
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
    )
    {
    }

    public static function make(
        string $emissionDate,
        DocumentType $documentType,
        string $ruc,
        Environment $environment,
        string|int $sequential,
        string|int|null $numericCode = null,
        string|int $establishmentCode = '001',
        string|int $emissionPointCode = '001',
        EmissionType $emissionType = EmissionType::Normal
    ): AccessKeyData
    {
        return new self(
            emissionDate: self::parseDate($emissionDate),
            documentType: $documentType,
            ruc: NumericString::fixed($ruc, 13, 'ruc'),
            environment: $environment,
            establishmentCode: NumericString::fixed($establishmentCode, 3, 'establishment_code'),
            emissionPointCode: NumericString::fixed($emissionPointCode, 3, 'emission_point_code'),
            sequential: NumericString::padded($sequential, 9, 'sequential'),
            numericCode: $numericCode === null ? self::generateNumericCode() : NumericString::fixed($numericCode, 8, 'numeric_code'),
            emissionType: $emissionType
        );
    }

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

    private static function parseDate(string $date): DateTimeImmutable
    {
        try
        {
            return new DateTimeImmutable($date);
        }
        catch (Throwable)
        {
            throw AccessKeyException::invalidDate($date);
        }
    }

    /**
     * @throws RandomException
     */
    public static function generateNumericCode(): string
    {
        return str_pad(
            (string) random_int(0, 99999999),
            8,
            '0',
            STR_PAD_LEFT
        );
    }
}