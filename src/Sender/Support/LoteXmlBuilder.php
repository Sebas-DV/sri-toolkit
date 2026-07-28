<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Support;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\Sender\Exceptions\BatchException;

/**
 * Builds the SRI batch (lote) XML envelope.
 *
 * Wraps up to 50 signed vouchers under a single batch access key, enforcing the
 * SRI limits of 50 vouchers and 500 kB per batch.
 */
final readonly class LoteXmlBuilder
{
    /**
     * @param string $loteAccessKey The 49-digit batch access key.
     * @param string $ruc The issuer RUC.
     * @param list<string> $signedXmls The signed voucher XML documents.
     * @return string The batch XML.
     * @throws BatchException When the batch is empty or exceeds the SRI limits.
     * @throws DOMException
     */
    public function build(string $loteAccessKey, string $ruc, array $signedXmls): string
    {
        if ($signedXmls === [])
        {
            throw BatchException::empty();
        }

        if (count($signedXmls) > BatchException::MAX_VOUCHERS)
        {
            throw BatchException::tooManyVouchers(count($signedXmls));
        }

        $document = new DOMDocument('1.0', 'UTF-8');

        $lote = $document->createElement('lote');
        $lote->setAttribute('version', '1.0.0');
        $document->appendChild($lote);

        $lote->appendChild($document->createElement('claveAcceso', $loteAccessKey));
        $lote->appendChild($document->createElement('ruc', $ruc));

        $vouchers = $document->createElement('comprobantes');
        $lote->appendChild($vouchers);

        foreach ($signedXmls as $signedXml)
        {
            $voucher = $document->createElement('comprobante');
            $voucher->appendChild($document->createCDATASection($this->stripDeclaration($signedXml)));
            $vouchers->appendChild($voucher);
        }

        $xml = $document->saveXML() ?: '';

        $size = strlen($xml);

        if ($size > BatchException::MAX_SIZE_BYTES)
        {
            throw BatchException::tooLarge($size);
        }

        return $xml;
    }

    private function stripDeclaration(string $xml): string
    {
        return preg_replace('/^\s*<\?xml[^>]*\?>\s*/', '', $xml) ?? $xml;
    }
}
