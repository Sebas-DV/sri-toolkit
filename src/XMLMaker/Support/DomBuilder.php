<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Support;

use DOMDocument;
use DOMElement;
use DOMException;

final readonly class DomBuilder
{
    public function __construct(
        private DOMDocument $document,
    ) {
    }

    /**
     * @throws DOMException
     */
    public function append(DOMElement $parent, string $name, string|int|float|null $value): ?DOMElement
    {
        if ($value === null || $value === '')
        {
            return null;
        }

        $element = $this->document->createElement($name);
        $element->appendChild($this->document->createTextNode((string) $value));
        $parent->appendChild($element);

        return $element;
    }

    /**
     * @throws DOMException
     */
    public function child(DOMElement $parent, string $name): DOMElement
    {
        $element = $this->document->createElement($name);
        $parent->appendChild($element);

        return $element;
    }
}
