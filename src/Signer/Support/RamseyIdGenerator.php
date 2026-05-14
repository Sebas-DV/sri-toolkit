<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Support;

use MTZ\Toolkit\Signer\Contract\IdGeneratorInterface;
use Ramsey\Uuid\Uuid;

final class RamseyIdGenerator implements IdGeneratorInterface
{
    public function generate(): string
    {
        return Uuid::uuid4()->toString();
    }
}