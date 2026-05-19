<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Security\Contracts;

interface StringEncrypterInterface
{
    public function encrypt(string $value): string;
    public function decrypt(string $value): string;
}
