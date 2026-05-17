<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Services;

use DOMDocument;
use DOMElement;
use DOMException;
use MTZ\Toolkit\Signer\Config\SignerConfig;
use MTZ\Toolkit\Signer\Contract\ClockInterface;
use MTZ\Toolkit\Signer\Contract\IdGeneratorInterface;
use MTZ\Toolkit\Signer\Data\CertificateData;
use MTZ\Toolkit\Signer\Data\SignedXmlResult;
use MTZ\Toolkit\Signer\Exceptions\SignerException;
use MTZ\Toolkit\Signer\Support\OpenSslSignature;

final class XadesBesXmlSigner
{
    private array $ids = [];
    private string $signedAt = '';
    private string $hashDocument = '';
    private string $hasKeyInfo = '';
    private string $hashSignedProperties = '';

    public function __construct(
        private readonly SignerConfig $config,
        private readonly ClockInterface $clock,
        private readonly IdGeneratorInterface $idGenerator,
        private readonly OpenSslSignature $openSslSignature,
    )
    {
    }

    /**
     * @throws DOMException
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

        return new SignedXmlResult(
            xml: $document->saveXML(),
            signatureId: $this->ids['signature'],
            signedAt: $this->signedAt,
        );
    }

    /**
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
            $this->canonicalizeElement($document, $keyInfo, 'ds:KeyInfo', ['xmlns:ds' => $this->config->signatureNamespace])
        );

        return $keyInfo;
    }

    /**
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

        $signingCertificate  = $document->createElement('xades:SigningCertificate');
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
            ])
        );

        return $signedProperties;
    }

    /**
     * @throws DOMException
     */
    private function createSignedInfo(DOMDocument $document): DOMElement
    {
        $signedInfo = $document->createElement('ds:SignedInfo');
        $signedInfo->setAttribute('Id', 'SignedInfo-' . $this->ids['signedInfo']);

        $canonicalizationMethod = $document->createElement('ds:CanonicalizationMethod');
        $canonicalizationMethod->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');

        $signatureMethod = $document->createElement('ds:SignatureMethod');
        $canonicalizationMethod->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');

        $signedInfo->appendChild($canonicalizationMethod);
        $signedInfo->appendChild($signatureMethod);

        $signedInfo->appendChild($this->createDocumentReference($document));
        $signedInfo->appendChild($this->createSignedPropertiesReference($document));
        $signedInfo->appendChild($this->createCertificateReference($document));

        return $signedInfo;
    }

    /**
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
     * @throws DOMException
     */
    private function createSignatureValue(
        DOMDocument $document,
        DOMElement $signedInfo,
        CertificateData $certificateData,
    ): DOMElement
    {
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
     * @throws DOMException
     */
    private function digestMethod(DOMDocument $document): DOMElement
    {
        $digestMethod = $document->createElement('ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');

        return $digestMethod;
    }

    private function canonicalizeElement(
        DOMDocument $document,
        DOMElement $element,
        string $tagName,
        array $nameSpaces = []
    ): string
    {
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
                array_keys($nameSpaces)
            ));

            $xml = preg_replace(
                '/<' . preg_quote($tagName, '/') . '([\s>])/',
                '<' . $tagName . ' ' . $namespaceString . '$1',
                $xml
            ) ?? $xml;
        }

        $temp = new DOMDocument($this->config->xmlVersion, $this->config->encoding);

        if (! $temp->loadXML($xml))
        {
            throw new SignerException("Could not canonicalize  $tagName.");
        }

        return $temp->C14N();
    }

    private function sha1Base64(string $content): string
    {
        return base64_encode(sha1($content, true));
    }

    private function generateIds(): array
    {
        return [
            'certificate' => $this->idGenerator->generate(),
            'certificateReference' => $this->idGenerator->generate(),
            'signature' => $this->idGenerator->generate(),
            'signatureProperties' => $this->idGenerator->generate(),
            'signedInfo' => $this->idGenerator->generate(),
            'signedPropertiesReference' => $this->idGenerator->generate(),
            'reference' => $this->idGenerator->generate(),
            'signatureValue' => $this->idGenerator->generate(),
            'object' => $this->idGenerator->generate(),
        ];
    }
}
