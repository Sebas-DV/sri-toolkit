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
use MTZ\Toolkit\Signer\Exceptions\InvalidXmlException;
use MTZ\Toolkit\Signer\Exceptions\SignerException;
use MTZ\Toolkit\Signer\Services\Pkcs12CertificateLoader;
use MTZ\Toolkit\Signer\Services\XadesBesXmlSigner;
use MTZ\Toolkit\Signer\Services\XmlDocumentLoader;
use MTZ\Toolkit\Signer\Support\OpenSslSignature;
use MTZ\Toolkit\Signer\Support\RamseyIdGenerator;
use MTZ\Toolkit\Signer\Support\SystemClock;

/**
 * Main entry point for signing XML documents with XAdES-BES.
 *
 * Orchestrates certificate loading, XML validation, and the signing
 * process using configured dependencies with sensible defaults.
 */
final class Signer
{
    /**
     * @var ?DOMDocument The loaded DOM document to be signed.
     */
    private ?DOMDocument $document = null;

    /**
     * @var CertificateData The parsed certificate and private key data.
     */
    private readonly CertificateData $certificateData;

    /**
     * @param string $certificatePath The path to the PKCS#12 certificate file.
     * @param string $certificatePassword The password for the certificate.
     * @param SignerConfig $config Configuration options for the signing process.
     * @param ?CertificateLoaderInterface $certificateLoader Optional custom certificate loader.
     * @param ?ClockInterface $clock Optional custom clock implementation.
     * @param ?IdGeneratorInterface $idGenerator Optional custom ID generator.
     */
    public function __construct(
        string $certificatePath,
        string $certificatePassword,
        private readonly SignerConfig $config = new SignerConfig(),
        ?CertificateLoaderInterface $certificateLoader = null,
        private readonly ?ClockInterface $clock = null,
        private readonly ?IdGeneratorInterface $idGenerator = null,
    ) {
        $certificateLoader ??= new Pkcs12CertificateLoader();

        $this->certificateData = $certificateLoader->load(
            certificatePath: $certificatePath,
            certificatePassword: $certificatePassword,
        );
    }

    /**
     * Load and validate the XML document to be signed.
     *
     * @param DOMDocument|string $xml A DOMDocument instance or a raw XML string.
     * @return $this Fluent interface.
     * @throws InvalidXmlException When the XML fails validation.
     */
    public function loadXml(DOMDocument|string $xml): self
    {
        $loader = new XmlDocumentLoader($this->config);

        $this->document = $loader->load($xml);

        return $this;
    }

    /**
     * Sign the loaded XML document and return the signed XML string.
     *
     * @return string The signed XML document.
     * @throws DOMException When an error occurs during XML manipulation.
     * @throws SignerException When no document has been loaded.
     */
    public function sign(): string
    {
        return $this->signAsResult()->xml;
    }

    /**
     * Sign the loaded XML document and return the full result including metadata.
     *
     * @return SignedXmlResult The signed document along with signature ID and timestamp.
     * @throws DOMException When an error occurs during XML manipulation.
     * @throws SignerException When no document has been loaded.
     */
    public function signAsResult(): SignedXmlResult
    {
        $signer = new XadesBesXmlSigner(
            config: $this->config,
            clock: $this->clock ?? new SystemClock(),
            idGenerator: $this->idGenerator ?? new RamseyIdGenerator(),
            openSslSignature: new OpenSslSignature(),
        );

        return $signer->sign(
            document: $this->document,
            certificateData: $this->certificateData,
        );
    }
}
