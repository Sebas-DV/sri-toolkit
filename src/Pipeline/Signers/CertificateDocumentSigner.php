<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Pipeline\Signers;

use MTZ\Toolkit\Pipeline\Contracts\DocumentSignerInterface;
use MTZ\Toolkit\Signer\Config\SignerConfig;
use MTZ\Toolkit\Signer\Signer;

/**
 * Adapts the XAdES-BES Signer to the pipeline signing contract using a PKCS#12 certificate.
 */
final readonly class CertificateDocumentSigner implements DocumentSignerInterface
{
    private Signer $signer;

    /**
     * @param string $certificatePath Path to the PKCS#12 (.p12/.pfx) certificate file.
     * @param string $certificatePassword Password for the certificate.
     * @param SignerConfig $config Optional signing configuration.
     */
    public function __construct(
        string $certificatePath,
        string $certificatePassword,
        SignerConfig $config = new SignerConfig(),
    ) {
        $this->signer = new Signer(
            certificatePath: $certificatePath,
            certificatePassword: $certificatePassword,
            config: $config,
        );
    }

    public function sign(string $xml): string
    {
        return $this->signer->loadXml($xml)->sign();
    }
}
