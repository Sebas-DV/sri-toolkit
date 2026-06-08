<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Services;

use DOMDocument;
use DOMElement;
use DOMException;
use MTZ\Toolkit\Signer\Config\SignerConfig;
use MTZ\Toolkit\Signer\Contract\ClockInterface;
use MTZ\Toolkit\Signer\Contract\IdGeneratorInterface;
use MTZ\Toolkit\Signer\Contract\SignatureEngineInterface;
use MTZ\Toolkit\Signer\Data\CertificateData;
use MTZ\Toolkit\Signer\Data\SignedXmlResult;
use MTZ\Toolkit\Signer\Exceptions\SignerException;

/**
 * Builds and injects a XAdES-BES (Basic Electronic Signature) into an XML DOM.
 *
 * Constructs the full XMLDSig + XAdES structure including SignedInfo,
 * KeyInfo, SignedProperties, and SignatureValue elements following the
 * Ecuadorian SRI (Servicio de Rentas Internas) standard.
 */
final class XadesBesXmlSigner
{
    /**
     * @var array<string, string> Generated unique IDs for signature components.
     */
    private array $ids = [];

    /**
     * @var string The formatted signing timestamp.
     */
    private string $signedAt = '';

    /**
     * @var string The Base64-encoded SHA-1 hash of the canonicalized document.
     */
    private string $hashDocument = '';

    /**
     * @var string The Base64-encoded SHA-1 hash of the KeyInfo element.
     */
    private string $hasKeyInfo = '';

    /**
     * @var string The Base64-encoded SHA-1 hash of the SignedProperties element.
     */
    private string $hashSignedProperties = '';

    /**
     * @param SignerConfig $config Configuration for namespaces and document settings.
     * @param ClockInterface $clock Clock service for generating signing timestamps.
     * @param IdGeneratorInterface $idGenerator Service for generating unique signature IDs.
     * @param SignatureEngineInterface $openSslSignature The cryptographic signing engine.
     */
    public function __construct(
        private readonly SignerConfig $config,
        private readonly ClockInterface $clock,
        private readonly IdGeneratorInterface $idGenerator,
        private readonly SignatureEngineInterface $openSslSignature,
    ) {
    }

    /**
     * Sign the DOM document by appending the XAdES-BES signature structure.
     *
     * @param ?DOMDocument $document The XML document to sign (must have been loaded via loadXml).
     * @param CertificateData $certificateData The certificate and private key data.
     * @return SignedXmlResult The signed XML string with metadata.
     * @throws DOMException When an error occurs during DOM manipulation.
     */
    public function sign(?DOMDocument $document, CertificateData $certificateData): SignedXmlResult
    {
        if (! $document instanceof DOMDocument || $document->documentElement === null)
        {
            throw new SignerException('You must call loadXml() before sign() the XML.');
        }

        $this->ids = $this->generateIds();
        $this->signedAt = $this->clock->now($this->config->timeZone);
        $this->hashDocument = $this->sha1Base64($document->C14N());

        $signature = $this->createSignature($document, $certificateData);

        $document->documentElement->appendChild($signature);

        $xml = $document->saveXML();

        if ($xml === false)
        {
            throw new SignerException('Could not serialize signed XML.');
        }

        return new SignedXmlResult(
            xml: $xml,
            signatureId: $this->ids['signature'],
            signedAt: $this->signedAt,
        );
    }

    /**
     * Create the top-level Signature element with all child nodes.
     *
     * @param DOMDocument $document The DOM document being signed.
     * @param CertificateData $certificateData The certificate data to embed.
     * @return DOMElement The constructed ds:Signature element.
     * @throws DOMException
     */
    private function createSignature(DOMDocument $document, CertificateData $certificateData): DOMElement
    {
        $signature = $document->createElementNS($this->config->signatureNamespace, 'ds:Signature');
        $signature->setAttribute('Id', 'Signature-' . $this->ids['signature']);
        $signature->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ds', $this->config->signatureNamespace);

        $keyInfo = $this->createKeyInfo($document, $certificateData);
        $signedProperties = $this->createSignedProperties($document, $certificateData);
        $signedInfo = $this->createSignedInfo($document);
        $signatureValue = $this->createSignatureValue($document, $signedInfo, $certificateData);
        $object = $this->createObject($document, $signedProperties);

        $signature->appendChild($signedInfo);
        $signature->appendChild($signatureValue);
        $signature->appendChild($keyInfo);
        $signature->appendChild($object);

        return $signature;
    }

    /**
     * Build the ds:KeyInfo element containing the X.509 certificate and RSA key values.
     *
     * @param DOMDocument $document The DOM document being signed.
     * @param CertificateData $certificateData Certificate content, modulus, and exponent.
     * @return DOMElement The constructed ds:KeyInfo element.
     * @throws DOMException
     */
    private function createKeyInfo(DOMDocument $document, CertificateData $certificateData): DOMElement
    {
        $keyInfo = $document->createElement('ds:KeyInfo');
        $keyInfo->setAttribute('Id', 'Certificate-' . $this->ids['certificate']);

        $x509Data = $document->createElement('ds:X509Data');

        $x509Certificate = $document->createElement('ds:X509Certificate');
        $x509Certificate->nodeValue = $certificateData->certificateContent;

        $x509Data->appendChild($x509Certificate);
        $keyInfo->appendChild($x509Data);

        $keyValue = $document->createElement('ds:KeyValue');
        $rsaKeyValue = $document->createElement('ds:RSAKeyValue');

        $modulus = $document->createElement('ds:Modulus');
        $modulus->nodeValue = base64_encode($certificateData->modulus);

        $exponent = $document->createElement('ds:Exponent');
        $exponent->nodeValue = base64_encode($certificateData->exponent);

        $rsaKeyValue->appendChild($modulus);
        $rsaKeyValue->appendChild($exponent);
        $keyValue->appendChild($rsaKeyValue);
        $keyInfo->appendChild($keyValue);

        $this->hasKeyInfo = $this->sha1Base64(
            $this->canonicalizeElement($document, $keyInfo, 'ds:KeyInfo', ['xmlns:ds' => $this->config->signatureNamespace]),
        );

        return $keyInfo;
    }

    /**
     * Build the xades:SignedProperties element with signing time and certificate reference.
     *
     * @param DOMDocument $document The DOM document being signed.
     * @param CertificateData $certificateData The certificate data for the signing certificate reference.
     * @return DOMElement The constructed xades:SignedProperties element.
     * @throws DOMException
     */
    private function createSignedProperties(DOMDocument $document, CertificateData $certificateData): DOMElement
    {
        $signedProperties = $document->createElement('xades:SignedProperties');
        $signedProperties->setAttribute('Id', 'SignedProperties-' . $this->ids['signedProperties']);

        $signedSignatureProperties = $document->createElement('xades:SignedSignatureProperties');

        $signingTime = $document->createElement('xades:SigningTime');
        $signingTime->nodeValue = $this->signedAt;

        $signedSignatureProperties->appendChild($signingTime);

        $signingCertificate = $document->createElement('xades:SigningCertificate');
        $cert = $document->createElement('xades:Cert');

        $certDigest = $document->createElement('xades:CertDigest');

        $digestMethod = $document->createElement('ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');

        $digestValue = $document->createElement('ds:DigestValue');
        $digestValue->nodeValue = $this->sha1Base64(base64_decode($certificateData->certificateContent, true) ?: '');

        $certDigest->appendChild($digestMethod);
        $certDigest->appendChild($digestValue);
        $cert->appendChild($certDigest);

        $issuerSerial = $document->createElement('xades:IssuerSerial');

        $issuerName = $document->createElement('ds:X509IssuerName');
        $issuerName->nodeValue = $certificateData->issuerName;

        $serialNumber = $document->createElement('ds:X509SerialNumber');
        $serialNumber->nodeValue = $certificateData->serialNumber;

        $issuerSerial->appendChild($issuerName);
        $issuerSerial->appendChild($serialNumber);

        $cert->appendChild($issuerSerial);
        $signingCertificate->appendChild($cert);
        $signedSignatureProperties->appendChild($signingCertificate);

        $signedProperties->appendChild($signedSignatureProperties);

        $signedDataObjectProperties = $document->createElement('xades:SignedDataObjectProperties');

        $dataObjectFormat = $document->createElement('xades:DataObjectFormat');
        $dataObjectFormat->setAttribute('ObjectReference', '#DocumentRef-' . $this->ids['reference']);

        $description = $document->createElement('xades:Description');
        $description->nodeValue = 'Firma digital';

        $mimeType = $document->createElement('xades:MimeType');
        $mimeType->nodeValue = 'text/xml';

        $encoding = $document->createElement('xades:Encoding');
        $encoding->nodeValue = $this->config->encoding;

        $dataObjectFormat->appendChild($description);
        $dataObjectFormat->appendChild($mimeType);
        $dataObjectFormat->appendChild($encoding);

        $signedDataObjectProperties->appendChild($dataObjectFormat);
        $signedProperties->appendChild($signedDataObjectProperties);

        $this->hashSignedProperties = $this->sha1Base64(
            $this->canonicalizeElement($document, $signedProperties, 'xades:SignedProperties', [
                'xmlns:ds' => $this->config->signatureNamespace,
                'xmlns:xades' => $this->config->xadesNamespace,
            ]),
        );

        return $signedProperties;
    }

    /**
     * Build the ds:SignedInfo element with references to the document, SignedProperties, and certificate.
     *
     * @param DOMDocument $document The DOM document being signed.
     * @return DOMElement The constructed ds:SignedInfo element.
     * @throws DOMException
     */
    private function createSignedInfo(DOMDocument $document): DOMElement
    {
        $signedInfo = $document->createElement('ds:SignedInfo');
        $signedInfo->setAttribute('Id', 'SignedInfo-' . $this->ids['signedInfo']);

        $canonicalizationMethod = $document->createElement('ds:CanonicalizationMethod');
        $canonicalizationMethod->setAttribute(
            'Algorithm',
            'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
        );

        $signatureMethod = $document->createElement('ds:SignatureMethod');
        $signatureMethod->setAttribute(
            'Algorithm',
            'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
        );

        $signedInfo->appendChild($canonicalizationMethod);
        $signedInfo->appendChild($signatureMethod);

        $signedInfo->appendChild($this->createDocumentReference($document));
        $signedInfo->appendChild($this->createSignedPropertiesReference($document));

        if ($this->config->includeCertificateReference)
        {
            $signedInfo->appendChild($this->createCertificateReference($document));
        }

        return $signedInfo;
    }

    /**
     * Build the ds:Reference element pointing to the main document to be signed.
     *
     * @param DOMDocument $document The DOM document being signed.
     * @return DOMElement The constructed document reference element.
     * @throws DOMException
     */
    private function createDocumentReference(DOMDocument $document): DOMElement
    {
        $reference = $document->createElement('ds:Reference');
        $reference->setAttribute('Id', 'DocumentRef-' . $this->ids['reference']);
        $reference->setAttribute('URI', '#' . $this->config->documentReferenceId);

        $transforms = $document->createElement('ds:Transforms');

        $transform = $document->createElement('ds:Transform');
        $transform->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');

        $transforms->appendChild($transform);
        $reference->appendChild($transforms);

        $reference->appendChild($this->digestMethod($document));

        $digestValue = $document->createElement('ds:DigestValue');
        $digestValue->nodeValue = $this->hashDocument;

        $reference->appendChild($digestValue);

        return $reference;
    }

    /**
     * Build the ds:Reference element referencing the SignedProperties.
     *
     * @param DOMDocument $document The DOM document being signed.
     * @return DOMElement The constructed SignedProperties reference element.
     * @throws DOMException
     */
    private function createSignedPropertiesReference(DOMDocument $document): DOMElement
    {
        $reference = $document->createElement('ds:Reference');
        $reference->setAttribute('Id', 'SignedPropertiesRef-' . $this->ids['signedPropertiesReference']);
        $reference->setAttribute('Type', 'http://uri.etsi.org/01903#SignedProperties');
        $reference->setAttribute('URI', '#SignedProperties-' . $this->ids['signedProperties']);

        $reference->appendChild($this->digestMethod($document));

        $digestValue = $document->createElement('ds:DigestValue');
        $digestValue->nodeValue = $this->hashSignedProperties;

        $reference->appendChild($digestValue);

        return $reference;
    }

    /**
     * Build the ds:Reference element referencing the X.509 certificate in KeyInfo.
     *
     * @param DOMDocument $document The DOM document being signed.
     * @return DOMElement The constructed certificate reference element.
     * @throws DOMException
     */
    private function createCertificateReference(DOMDocument $document): DOMElement
    {
        $reference = $document->createElement('ds:Reference');
        $reference->setAttribute('Id', 'CertificateRef-' . $this->ids['reference']);
        $reference->setAttribute('URI', '#Certificate-' . $this->ids['certificate']);

        $reference->appendChild($this->digestMethod($document));

        $digestValue = $document->createElement('ds:DigestValue');
        $digestValue->nodeValue = $this->hasKeyInfo;

        $reference->appendChild($digestValue);

        return $reference;
    }

    /**
     * Build the ds:SignatureValue element containing the cryptographic signature.
     *
     * @param DOMDocument $document The DOM document being signed.
     * @param DOMElement $signedInfo The SignedInfo element to canonicalize and sign.
     * @param CertificateData $certificateData The private key used for signing.
     * @return DOMElement The constructed ds:SignatureValue element.
     * @throws DOMException
     */
    private function createSignatureValue(
        DOMDocument $document,
        DOMElement $signedInfo,
        CertificateData $certificateData,
    ): DOMElement {
        $signatureValue = $document->createElement('ds:SignatureValue');
        $signatureValue->setAttribute('Id', 'SignatureValue-' . $this->ids['signatureValue']);

        $canonicalized = $this->canonicalizeElement($document, $signedInfo, 'ds:SignedInfo', [
            'xmlns:ds' => $this->config->signatureNamespace,
        ]);

        $signatureValue->nodeValue = $this->openSslSignature->signSha1(
            content: $canonicalized,
            privateKeyPem: $certificateData->privateKeyPem,
        );

        return $signatureValue;
    }

    /**
     * Build the ds:Object wrapper containing the XAdES QualifyingProperties.
     *
     * @param DOMDocument $document The DOM document being signed.
     * @param DOMElement $signedProperties The SignedProperties element to wrap.
     * @return DOMElement The constructed ds:Object element.
     * @throws DOMException
     */
    private function createObject(DOMDocument $document, DOMElement $signedProperties): DOMElement
    {
        $object = $document->createElement('ds:Object');
        $object->setAttribute('Id', 'SignatureObject-' . $this->ids['object']);

        $qualifyingProperties = $document->createElement('xades:QualifyingProperties');
        $qualifyingProperties->setAttribute('xmlns:xades', $this->config->xadesNamespace);
        $qualifyingProperties->setAttribute('Target', '#Signature-' . $this->ids['signature']);
        $qualifyingProperties->appendChild($signedProperties);

        $object->appendChild($qualifyingProperties);

        return $object;
    }

    /**
     * Build a ds:DigestMethod element configured for SHA-1.
     *
     * @param DOMDocument $document The DOM document.
     * @return DOMElement The digest method element.
     * @throws DOMException
     */
    private function digestMethod(DOMDocument $document): DOMElement
    {
        $digestMethod = $document->createElement('ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');

        return $digestMethod;
    }

    /**
     * Canonicalize an XML element with injected namespace declarations.
     *
     * Serializes the element, injects the given namespace attributes, re-parses,
     * and returns the C14N canonicalized string.
     *
     * @param DOMDocument $document The DOM document.
     * @param DOMElement $element The element to canonicalize.
     * @param string $tagName The tag name for namespace injection.
     * @param array<string, string> $nameSpaces Namespace declarations to inject.
     * @return string The canonicalized XML string.
     */
    private function canonicalizeElement(
        DOMDocument $document,
        DOMElement $element,
        string $tagName,
        array $nameSpaces = [],
    ): string {
        $xml = $document->saveXML($element);

        if ($xml === false)
        {
            throw new SignerException("Could not serialize $tagName.");
        }

        if ($nameSpaces !== [])
        {
            $namespaceString = implode(' ', array_map(
                static fn (string $value, string $key): string => "$key=\"$value\"",
                $nameSpaces,
                array_keys($nameSpaces),
            ));

            $xml = preg_replace(
                '/<' . preg_quote($tagName, '/') . '([\s>])/',
                '<' . $tagName . ' ' . $namespaceString . '$1',
                $xml,
            ) ?? $xml;
        }

        $temp = new DOMDocument($this->config->xmlVersion, $this->config->encoding);

        if (! $temp->loadXML($xml))
        {
            throw new SignerException("Could not canonicalize  $tagName.");
        }

        return $temp->C14N();
    }

    /**
     * Compute the Base64-encoded SHA-1 hash of a string.
     *
     * @param string $content The content to hash.
     * @return string The Base64-encoded SHA-1 digest.
     */
    private function sha1Base64(string $content): string
    {
        return base64_encode(sha1($content, true));
    }

    /**
     * Generate all unique IDs needed for the signature elements.
     *
     * @return array<string, string> Map of ID keys to generated UUID strings.
     */
    private function generateIds(): array
    {
        return [
            'certificate' => $this->idGenerator->generate(),
            'certificateReference' => $this->idGenerator->generate(),
            'signature' => $this->idGenerator->generate(),
            'signedProperties' => $this->idGenerator->generate(),
            'signedInfo' => $this->idGenerator->generate(),
            'signedPropertiesReference' => $this->idGenerator->generate(),
            'reference' => $this->idGenerator->generate(),
            'signatureValue' => $this->idGenerator->generate(),
            'object' => $this->idGenerator->generate(),
        ];
    }
}
