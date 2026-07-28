<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Feature\XMLMaker;

use DOMException;
use MTZ\Toolkit\Tests\Support\XMLMaker\SampleXmlPayloads;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Validation\XsdValidator;
use MTZ\Toolkit\XMLMaker\XMLMaker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Guards that every generated document validates against the bundled official
 * SRI XSD, so a schema regression (SRI reception error 35) fails the suite.
 */
final class XsdConformanceTest extends TestCase
{
    /**
     * @throws DOMException
     */
    #[Test]
    #[DataProvider('documents')]
    public function it_generates_schema_valid_xml(XmlGenerationData $data): void
    {
        $xml = (new XMLMaker())->generate($data)->toString();

        $errors = (new XsdValidator())->validate($xml, $data->documentType);

        $this->assertSame(
            [],
            $errors,
            sprintf(
                "%s XML failed XSD validation:\n%s",
                $data->documentType->value,
                implode("\n", $errors),
            ),
        );
    }

    /**
     * @return iterable<string, array{XmlGenerationData}>
     */
    public static function documents(): iterable
    {
        foreach (SampleXmlPayloads::all() as $key => $data)
        {
            yield $key => [$data];
        }
    }
}
