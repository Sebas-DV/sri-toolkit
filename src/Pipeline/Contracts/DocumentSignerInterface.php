<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Pipeline\Contracts;

/**
 * Signs an SRI XML document and returns the signed XML.
 */
interface DocumentSignerInterface
{
    /**
     * @param string $xml The unsigned XML document.
     * @return string The signed XML document.
     */
    public function sign(string $xml): string;
}
