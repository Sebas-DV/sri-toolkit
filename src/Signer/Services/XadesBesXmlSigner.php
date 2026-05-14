<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Services;

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

    public function sign(?\DOMDocument $document, CertificateData $certificateData): SignedXmlResult
    {
        if (! $document instanceof \DOMDocument || $document->documentElement === null)
        {
            throw new SignerException('You must call loadXml() before sign() the XML.')
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

    private function createSignature(\DOMDocument $document, CertificateData $certificateData): \DOMElement
    {
        $signature = $document->createElementNS($this->config->signatureNamespace, 'ds:Signature');
        $signature->setAttribute('Id', 'Signature-' . $this->ids['signature']);
        $signature->setAttribute('http://www.w3.org/2000/xmlns/', 'xmlns:ds', $this->config->signatureNamespace);

        $keyInfo = $this->createKeyInfo($document, $certificateData);
        $signedProperties = $this->createSignedProperties($document, $certificateData);
        $signedInfo = $this->createSinedInfo($document);
        $signatureValue = $this->createSignatureValue($document, $signedInfo, $certificateData);
        $object = $this->createObject($document, $signedProperties);

        $signature->appendChild($signedInfo);
        $signature->appendChild($signatureValue);
        $signature->appendChild($keyInfo);
        $signature->appendChild($object);

        return $signature;
    }

    private function createKeyInfo(\DOMDocument $document, CertificateData $certificateData): \DOMElement
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
}
