<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support\Signer;

use RuntimeException;

final class TemporaryCertificateFactory
{
    public static function make(?string $password = null): GeneratedCertificate
    {
        if (! extension_loaded('openssl'))
        {
            throw new RuntimeException('The OpenSSL extension is required to generate test certificates.');
        }

        $password ??= 'test-password';

        try
        {
            return self::makeWithPhpOpenSsl($password);
        } catch (RuntimeException)
        {
            return self::makeWithOpenSslCli($password);
        }
    }

    private static function makeWithPhpOpenSsl(string $password): GeneratedCertificate
    {
        self::clearOpenSslErrors();

        $directory = self::createTemporaryDirectory();
        $configPath = self::createOpenSslConfig($directory);
        $p12Path = $directory . DIRECTORY_SEPARATOR . 'certificate.p12';

        $previousOpenSslConfig = getenv('OPENSSL_CONF');

        putenv('OPENSSL_CONF=' . $configPath);

        try
        {
            $privateKey = openssl_pkey_new([
                'config' => $configPath,
                'digest_alg' => 'sha256',
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            ]);

            if ($privateKey === false)
            {
                throw new RuntimeException('Failed to generate private key: ' . self::openSslError());
            }

            $distinguishedName = [
                'countryName' => 'EC',
                'stateOrProvinceName' => 'Pichincha',
                'localityName' => 'Quito',
                'organizationName' => 'MTZ Toolkit Tests',
                'organizationalUnitName' => 'Testing',
                'commonName' => 'MTZ Testing Certificate',
                'emailAddress' => 'testing@example.com',
            ];

            $csr = openssl_csr_new(
                $distinguishedName,
                $privateKey,
                [
                    'config' => $configPath,
                    'digest_alg' => 'sha256',
                ],
            );

            if ($csr === false || $csr === true)
            {
                throw new RuntimeException('Failed to generate CSR: ' . self::openSslError());
            }

            $certificate = openssl_csr_sign(
                $csr,
                null,
                $privateKey,
                365,
                [
                    'config' => $configPath,
                    'digest_alg' => 'sha256',
                ],
                123456789,
            );

            if ($certificate === false)
            {
                throw new RuntimeException('Failed to self-sign certificate: ' . self::openSslError());
            }

            $exported = openssl_pkcs12_export_to_file(
                $certificate,
                $p12Path,
                $privateKey,
                $password,
                [
                    'config' => $configPath,
                    'friendly_name' => 'MTZ Toolkit Test Certificate',
                ],
            );

            if (! $exported)
            {
                throw new RuntimeException('Failed to export PKCS#12 certificate: ' . self::openSslError());
            }

            return new GeneratedCertificate(
                path: $p12Path,
                password: $password,
                temporaryFiles: [$configPath],
                temporaryDirectory: $directory,
            );
        } finally
        {
            if ($previousOpenSslConfig === false)
            {
                putenv('OPENSSL_CONF');
            } else
            {
                putenv('OPENSSL_CONF=' . $previousOpenSslConfig);
            }
        }
    }

    private static function makeWithOpenSslCli(string $password): GeneratedCertificate
    {
        $openssl = self::findOpenSslBinary();

        if ($openssl === null)
        {
            throw new RuntimeException(
                'Could not generate test certificate with PHP OpenSSL, and the openssl CLI was not found.',
            );
        }

        $directory = self::createTemporaryDirectory();

        $configPath = self::createOpenSslConfig($directory);
        $keyPath = $directory . DIRECTORY_SEPARATOR . 'private-key.pem';
        $certPath = $directory . DIRECTORY_SEPARATOR . 'certificate.pem';
        $p12Path = $directory . DIRECTORY_SEPARATOR . 'certificate.p12';

        self::runCommand([
            $openssl,
            'req',
            '-x509',
            '-newkey',
            'rsa:2048',
            '-keyout',
            $keyPath,
            '-out',
            $certPath,
            '-days',
            '365',
            '-nodes',
            '-sha256',
            '-config',
            $configPath,
        ]);

        self::runCommand([
            $openssl,
            'pkcs12',
            '-export',
            '-out',
            $p12Path,
            '-inkey',
            $keyPath,
            '-in',
            $certPath,
            '-passout',
            'pass:' . $password,
            '-name',
            'MTZ Toolkit Test Certificate',
        ]);

        if (! is_file($p12Path))
        {
            throw new RuntimeException('OpenSSL CLI did not generate the PKCS#12 file.');
        }

        return new GeneratedCertificate(
            path: $p12Path,
            password: $password,
            temporaryFiles: [
                $configPath,
                $keyPath,
                $certPath,
            ],
            temporaryDirectory: $directory,
        );
    }

    private static function createTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mtz-sri-cert-' . bin2hex(random_bytes(6));

        if (! mkdir($directory, 0777, true) && ! is_dir($directory))
        {
            throw new RuntimeException('Could not create temporary certificate directory.');
        }

        return $directory;
    }

    private static function createOpenSslConfig(string $directory): string
    {
        $path = $directory . DIRECTORY_SEPARATOR . 'openssl.cnf';

        $config = <<<CONFIG
[ req ]
default_bits = 2048
default_md = sha256
distinguished_name = req_distinguished_name
prompt = no
x509_extensions = v3_ca

[ req_distinguished_name ]
C = EC
ST = Pichincha
L = Quito
O = MTZ Toolkit Tests
OU = Testing
CN = MTZ Testing Certificate
emailAddress = testing@example.com

[ v3_ca ]
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid:always,issuer
basicConstraints = critical, CA:true
keyUsage = critical, digitalSignature, keyCertSign, cRLSign
CONFIG;

        file_put_contents($path, $config);

        return $path;
    }

    private static function findOpenSslBinary(): ?string
    {
        $candidates = [
            'openssl',
            'openssl.exe',
            'C:\\Program Files\\Git\\usr\\bin\\openssl.exe',
            'C:\\Program Files\\OpenSSL-Win64\\bin\\openssl.exe',
            'C:\\Program Files\\OpenSSL-Win32\\bin\\openssl.exe',
        ];

        foreach ($candidates as $candidate)
        {
            $output = [];
            $exitCode = 1;

            @exec(self::escape($candidate) . ' version 2>&1', $output, $exitCode);

            if ($exitCode === 0)
            {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param string[] $parts
     */
    private static function runCommand(array $parts): void
    {
        $command = implode(' ', array_map(self::escape(...), $parts)) . ' 2>&1';

        $output = [];
        $exitCode = 0;

        exec($command, $output, $exitCode);

        if ($exitCode !== 0)
        {
            throw new RuntimeException(
                "Command failed with exit code {$exitCode}: " . implode(PHP_EOL, $output),
            );
        }
    }

    private static function escape(string $value): string
    {
        return escapeshellarg($value);
    }

    private static function clearOpenSslErrors(): void
    {
        while (openssl_error_string() !== false)
        {
        }
    }

    private static function openSslError(): string
    {
        $errors = [];

        while ($error = openssl_error_string())
        {
            $errors[] = $error;
        }

        return $errors === [] ? 'unknown OpenSSL error' : implode(' | ', $errors);
    }
}
