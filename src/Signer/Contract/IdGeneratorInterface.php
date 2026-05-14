<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Contract;

interface IdGeneratorInterface
{
    public function generate(): string;
}