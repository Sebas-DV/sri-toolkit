<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Support;

use DOMDocument;
use DOMElement;
use DOMException;

/**
 * Convenience utility for building DOM elements in SRI XML documents.
 *
 * Wraps a DOMDocument to simplify creating and appending child elements
 * with text content.
 */
final readonly class DomBuilder
{
    /**
     * @param DOMDocument $document The DOM document to build elements within.
     */
    public function __construct(
        private DOMDocument $document,
    ) {
    }

    /**
     * Appends a child element with text content to a parent, skipping null/empty values.
     *
     * @param DOMElement $parent The parent element to append to.
     * @param string $name The tag name of the child element.
     * @param string|int|float|null $value The text content value (null or empty string skips creation).
     * @return DOMElement|null The created element, or null if the value was skipped.
     * @throws DOMException When the element cannot be created.
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
     * Creates a child element and appends it to a parent.
     *
     * @param DOMElement $parent The parent element to append to.
     * @param string $name The tag name of the child element.
     * @return DOMElement The newly created and appended child element.
     * @throws DOMException When the element cannot be created.
     */
    public function child(DOMElement $parent, string $name): DOMElement
    {
        $element = $this->document->createElement($name);
        $parent->appendChild($element);

        return $element;
    }
}
