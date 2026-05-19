<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Certificates;

use Exception;
use MTZ\Toolkit\Certificates\Data\CertificateMaterial;
use MTZ\Toolkit\Certificates\Exceptions\CertificateStorageException;
use MTZ\Toolkit\Security\Contracts\StringEncrypterInterface;
use MTZ\Toolkit\Storage\Contracts\DocumentStorageInterface;

final readonly class CertificateMaterialResolver
{
    public function __construct(
        private DocumentStorageInterface $storage,
        private StorageCertificateRepository $repository,
        private StringEncrypterInterface $encrypter,
        private string $temporaryDirectory = '',
    ) {
    }

    /**
     * @throws Exception
     */
    public function resolve(string $ownerKey): CertificateMaterial
    {
        $certificate = $this->repository->get($ownerKey);
        $contents = $this->storage->get($certificate->path);

        $directory = $this->temporaryDirectory !== ''
            ? $this->temporaryDirectory
            : sys_get_temp_dir();

        if (! is_dir($directory) && mkdir($directory, 0755, true) && ! is_dir($directory))
        {
            throw CertificateStorageException::cannotCreateTemporaryFile();
        }

        $temporaryPath = tempnam($directory, 'mtz-sri-cert-');

        if ($temporaryPath === false)
        {
            throw CertificateStorageException::cannotCreateTemporaryFile();
        }

        $p12Path = $temporaryPath . '.p12';

        @unlink($temporaryPath);

        if (file_put_contents($p12Path, $contents) === false)
        {
            throw CertificateStorageException::cannotCreateTemporaryFile();
        }

        return new CertificateMaterial(
            path: $p12Path,
            password: $this->encrypter->decrypt($certificate->encryptPassword),
        );
    }

}
