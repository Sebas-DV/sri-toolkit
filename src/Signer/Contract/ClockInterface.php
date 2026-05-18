<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Contract;

interface ClockInterface
{
    public function now(string $timezone): string;
}
