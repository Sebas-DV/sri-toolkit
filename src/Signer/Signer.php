<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\Signer\Config\SignerConfig;
use MTZ\Toolkit\Signer\Contract\CertificateLoaderInterface;
use MTZ\Toolkit\Signer\Contract\ClockInterface;
use MTZ\Toolkit\Signer\Contract\IdGeneratorInterface;
use MTZ\Toolkit\Signer\Data\CertificateData;
use MTZ\Toolkit\Signer\Data\SignedXmlResult;
use MTZ\Toolkit\Signer\Services\Pkcs12CertificateLoader;
use MTZ\Toolkit\Signer\Services\XadesBesXmlSigner;
use MTZ\Toolkit\Signer\Services\XmlDocumentLoader;
use MTZ\Toolkit\Signer\Support\OpenSslSignature;
use MTZ\Toolkit\Signer\Support\RamseyIdGenerator;
use MTZ\Toolkit\Signer\Support\SystemClock;

final class Signer
{
    private ?DOMDocument $document = null;
    private readonly CertificateData $certificateData;

    public function __construct(
        string $certificatePath,
        string $certificatePassword,
        private readonly SignerConfig $config = new SignerConfig(),
        ?CertificateLoaderInterface $certificateLoader = null,
        private readonly ?ClockInterface $clock = null,
        private readonly ?IdGeneratorInterface $idGenerator = null
    )
    {
        $certificateLoader ??= new Pkcs12CertificateLoader();

        $this->certificateData = $certificateLoader->load(
            certificatePath: $certificatePath,
            certificatePassword: $certificatePassword,
        );
    }

    public function loadXml(DOMDocument|string $xml): self
    {
        $loader = new XmlDocumentLoader($this->config);

        $this->document = $loader->load($xml);

        return $this;
    }

    /**
     * @throws DOMException
     */
    public function sign(): string
    {
        return $this->signAsResult()->xml;
    }

    /**
     * @throws DOMException
     */
    public function signAsResult(): SignedXmlResult
    {
        $signer = new XadesBesXmlSigner(
            config: $this->config,
            clock: $this->clock ?? new SystemClock(),
            idGenerator: $this->idGenerator ?? new RamseyIdGenerator(),
            openSslSignature: new OpenSslSignature()
        );

        return $signer->sign(
            document: $this->document,
            certificateData: $this->certificateData,
        );
    }
}