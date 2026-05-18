<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\XMLMaker\Support\DomBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DomBuilderTest extends TestCase
{
    /**
     * @throws DOMException
     */
    #[Test]
    public function it_appends_text_node_to_parent(): void
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $root = $document->createElement('factura');

        $document->append($root);

        $builder = new DomBuilder($document);

        $builder->append($root, 'razonSocial', 'MTZ TEST');

        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<factura><razonSocial>MTZ TEST</razonSocial></factura>' . "\n",
            $document->saveXML(),
        );
    }

    /**
     * @throws DOMException
     */
    #[Test]
    public function it_does_not_append_empty_values(): void
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $root = $document->createElement('factura');

        $document->appendChild($root);

        $builder = new DomBuilder($document);

        $result = $builder->append($root, 'nombreComercial', null);

        $this->assertNull($result);
        $this->assertSame(0, $root->childNodes->length);
    }

    /**
     * @throws DOMException
     */
    #[Test]
    public function it_creates_child_element(): void
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $root = $document->createElement('factura');

        $document->appendChild($root);

        $builder = new DomBuilder($document);

        $child = $builder->child($root, 'infoTributaria');

        $this->assertSame('infoTributaria', $child->nodeName);
        $this->assertSame(1, $root->childNodes->length);
    }
}
