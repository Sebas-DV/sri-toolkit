<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support\Signer;

use MTZ\Toolkit\Signer\Contract\SignatureEngineInterface;

final class FakeSignatureEngine implements SignatureEngineInterface
{
    public array $signedContents = [];

    public function signSha1(string $content, string $privateKeyPem): string
    {
        $this->signedContents[] = [
            'content' => $content,
            'private_key' => $privateKeyPem,
        ];

        return base64_encode('FAKE_SIGNATURE_VALUE');
    }
}
