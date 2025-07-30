<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Filesystem\StoreFilesystemPersister;

final readonly class StorePresetZipper
{
    public function __construct(
        private StoreFilesystemPersister $storePresetRepository,
    ) {
    }

    public function zip(string $storePresetId): string
    {
        $dir = $this->storePresetRepository->getPresetDirectory($storePresetId);
        $zip = new \ZipArchive();
        $tmpZip = tempnam(sys_get_temp_dir(), 'preset_zip_');
        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create zip file');
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($dir) + 1);
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        return $tmpZip;
    }
}
