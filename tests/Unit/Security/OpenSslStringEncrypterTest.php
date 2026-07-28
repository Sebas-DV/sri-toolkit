<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Security;

use MTZ\Toolkit\Security\Exceptions\EncryptionException;
use MTZ\Toolkit\Security\OpenSslStringEncrypter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OpenSslStringEncrypterTest extends TestCase
{
    private const KEY = '0123456789abcdef0123456789abcdef'; // 32 bytes

    #[Test]
    public function it_round_trips_a_value(): void
    {
        $encrypter = new OpenSslStringEncrypter(self::KEY);

        $cipher = $encrypter->encrypt('certificate-password');

        $this->assertStringStartsWith('mtz:v1:', $cipher);
        $this->assertNotSame('certificate-password', $cipher);
        $this->assertSame('certificate-password', $encrypter->decrypt($cipher));
    }

    #[Test]
    public function it_loads_from_a_base64_key(): void
    {
        $cipher = (new OpenSslStringEncrypter(self::KEY))->encrypt('secret');

        $encrypter = OpenSslStringEncrypter::fromBase64Key(base64_encode(self::KEY));

        $this->assertSame('secret', $encrypter->decrypt($cipher));
    }

    #[Test]
    public function it_rejects_a_key_that_is_not_32_bytes(): void
    {
        $this->expectException(EncryptionException::class);

        new OpenSslStringEncrypter('too-short');
    }

    #[Test]
    public function it_fails_to_decrypt_a_value_encrypted_with_another_key(): void
    {
        $cipher = (new OpenSslStringEncrypter(self::KEY))->encrypt('secret');

        $this->expectException(EncryptionException::class);

        (new OpenSslStringEncrypter('ffffffffffffffffffffffffffffffff'))->decrypt($cipher);
    }

    #[Test]
    public function it_fails_to_decrypt_a_tampered_payload(): void
    {
        $encrypter = new OpenSslStringEncrypter(self::KEY);

        $this->expectException(EncryptionException::class);

        $encrypter->decrypt('mtz:v1:' . base64_encode('not-valid-json'));
    }

    #[Test]
    public function it_fails_to_decrypt_without_the_prefix(): void
    {
        $encrypter = new OpenSslStringEncrypter(self::KEY);

        $this->expectException(EncryptionException::class);

        $encrypter->decrypt(base64_encode('anything'));
    }
}
