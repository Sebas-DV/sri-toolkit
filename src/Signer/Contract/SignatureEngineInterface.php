<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Contract;

interface SignatureEngineInterface
{
    public function signSha1(string $content, string $privateKeyPem): string;
}
