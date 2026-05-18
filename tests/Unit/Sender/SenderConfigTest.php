<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Sender;

use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Enums\Environment;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SenderConfigTest extends TestCase
{
    #[Test]
    public function it_uses_testing_wsdl_urls_by_default(): void
    {
        $config = new SenderConfig();

        $this->assertStringContainsString('celcer.sri.gob.ec', $config->receptionWsdl());
        $this->assertStringContainsString('celcer.sri.gob.ec', $config->authorizationWsdl());
    }

    #[Test]
    public function it_uses_production_wsdl_urls_when_environment_is_production(): void
    {
        $config = new SenderConfig(
            environment: Environment::Production,
        );

        $this->assertStringContainsString('cel.sri.gob.ec', $config->receptionWsdl());
        $this->assertStringContainsString('cel.sri.gob.ec', $config->authorizationWsdl());

        $this->assertStringNotContainsString('celcer.sri.gob.ec', $config->receptionWsdl());
        $this->assertStringNotContainsString('celcer.sri.gob.ec', $config->authorizationWsdl());
    }

    #[Test]
    public function it_merges_custom_soap_options(): void
    {
        $config = new SenderConfig(
            soapOptions: [
                'connection_timeout' => 30,
                'trace' => 0,
            ],
        );

        $options = $config->normalizedSoapOptions();

        $this->assertSame(30, $options['connection_timeout']);
        $this->assertSame(0, $options['trace']);
        $this->assertSame(WSDL_CACHE_NONE, $options['cache_wsdl']);
        $this->assertTrue($options['exceptions']);
    }
}
