<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support\Pipeline;

use MTZ\Toolkit\Pipeline\Contracts\DocumentSignerInterface;

/**
 * Records the XML it was asked to sign and returns a marked signed variant.
 */
final class FakeDocumentSigner implements DocumentSignerInterface
{
    /**
     * @var list<string> The XML strings passed to sign().
     */
    public array $signed = [];

    public function sign(string $xml): string
    {
        $this->signed[] = $xml;

        return $xml . '<!-- signed -->';
    }
}
