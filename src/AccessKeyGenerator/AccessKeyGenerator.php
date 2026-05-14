<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator;

use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Services\CheckDigitCalculator;

final readonly class AccessKeyGenerator
{
    public function __construct(
        private CheckDigitCalculator $checkDigitCalculator = new CheckDigitCalculator(),
    )
    {
    }

    public function generate(AccessKeyData $data): string
    {
        $base = $data->toAccessKeyBase();

        $checkDigit = $this->checkDigitCalculator->calculate($base);

        return $base . $checkDigit;
    }
}