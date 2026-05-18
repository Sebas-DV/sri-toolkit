<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Feature\Signer;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\Signer\Signer;
use MTZ\Toolkit\Tests\Support\Signer\FakeClock;
use MTZ\Toolkit\Tests\Support\Signer\FakeIdGenerator;
use MTZ\Toolkit\Tests\Support\Signer\TemporaryCertificateFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SignerTest extends TestCase
{
    /**
     * @throws DOMException
     */
    #[Test]
    public function it_signs_a_sri_xml_from_public_api(): void
    {
        $certificate = TemporaryCertificateFactory::make();

        try
        {
            $signedXml = (new Signer(
                certificatePath: $certificate->path,
                certificatePassword: $certificate->password,
                clock: new FakeClock(),
                idGenerator: new FakeIdGenerator(),
            ))
                ->loadXml('<factura id="comprobante"><infoTributaria/></factura>')
                ->sign();

            $this->assertStringContainsString('<ds:Signature', $signedXml);
            $this->assertStringContainsString('<ds:SignedInfo', $signedXml);
            $this->assertStringContainsString('<ds:SignatureValue', $signedXml);
            $this->assertStringContainsString('<ds:X509Certificate', $signedXml);
            $this->assertStringContainsString('<xades:QualifyingProperties', $signedXml);
            $this->assertStringContainsString('<xades:SignedProperties', $signedXml);

            $document = new DOMDocument();

            $this->assertTrue($document->loadXML($signedXml));
        } finally
        {
            $certificate->cleanup();
        }
    }
}
