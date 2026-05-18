<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator;

use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Services\CheckDigitCalculator;

/**
 * Generates SRI (Servicio de Rentas Internas) access keys for Ecuadorian electronic documents.
 *
 * The access key is a 49-digit unique identifier composed of document metadata
 * plus a check digit computed via the modulo 11 algorithm.
 */
final readonly class AccessKeyGenerator
{
    /**
     * Constructs a new AccessKeyGenerator instance.
     *
     * @param CheckDigitCalculator $checkDigitCalculator The check digit calculator instance.
     */
    public function __construct(
        private CheckDigitCalculator $checkDigitCalculator = new CheckDigitCalculator(),
    ) {
    }

    /**
     * Generates a full 49-digit SRI access key from the provided document data.
     *
     * @param AccessKeyData $data The document data required to compose the access key.
     * @return string The complete 49-digit access key (base + check digit).
     */
    public function generate(AccessKeyData $data): string
    {
        $base = $data->toAccessKeyBase();

        $checkDigit = $this->checkDigitCalculator->calculate($base);

        return $base . $checkDigit;
    }
}
