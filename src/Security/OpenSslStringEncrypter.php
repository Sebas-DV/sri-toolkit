<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Security;

use MTZ\Toolkit\Security\Contracts\StringEncrypterInterface;
use MTZ\Toolkit\Security\Exceptions\EncryptionException;
use Throwable;

final readonly class OpenSslStringEncrypter implements StringEncrypterInterface
{
    private const CIPHER = 'aes-256-gcm';

    private const PREFIX = 'mtz:v1:';

    public function __construct(
        private string $key,
    ) {
        if (strlen($this->key) !== 32)
        {
            throw EncryptionException::invalidKey();
        }
    }

    public static function fromBase64Key(string $base64Key): self
    {
        $key = base64_decode($base64Key, true);

        if ($key === false || strlen($key) !== 32)
        {
            throw EncryptionException::invalidKey();
        }

        return new self($key);
    }

    public function encrypt(string $value): string
    {
        try
        {
            $iv = random_bytes(12);
            $tag = '';

            $cipherText = openssl_encrypt(
                $value,
                self::CIPHER,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '',
                16,
            );

            if ($cipherText === false)
            {
                throw EncryptionException::encryptionFailed();
            }

            $payload = json_encode([
                'cipher' => self::CIPHER,
                'iv' => base64_encode($iv),
                'tag' => base64_encode($tag),
                'value' => base64_encode($cipherText),
            ], JSON_THROW_ON_ERROR);

            return self::PREFIX . base64_encode($payload);
        } catch (Throwable $e)
        {
            if ($e instanceof EncryptionException)
            {
                throw $e;
            }

            throw EncryptionException::encryptionFailed($e);
        }
    }

    public function decrypt(string $payload): string
    {
        try
        {
            if (! str_starts_with($payload, self::PREFIX))
            {
                throw EncryptionException::decryptionFailed();
            }

            $encodedJson = substr($payload, strlen(self::PREFIX));
            $json = base64_decode($encodedJson, true);

            if ($json === false)
            {
                throw EncryptionException::decryptionFailed();
            }

            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($data))
            {
                throw EncryptionException::decryptionFailed();
            }

            $iv = $this->decode($data['iv'] ?? null);
            $tag = $this->decode($data['tag'] ?? null);
            $cipherText = $this->decode($data['value'] ?? null);

            $plainText = openssl_decrypt(
                $cipherText,
                self::CIPHER,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
            );

            if ($plainText === false)
            {
                throw EncryptionException::decryptionFailed();
            }

            return $plainText;
        } catch (Throwable $e)
        {
            if ($e instanceof EncryptionException)
            {
                throw $e;
            }

            throw EncryptionException::decryptionFailed($e);
        }
    }

    private function decode(mixed $value): string
    {
        if (! is_string($value))
        {
            throw EncryptionException::decryptionFailed();
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false)
        {
            throw EncryptionException::decryptionFailed();
        }

        return $decoded;
    }
}
