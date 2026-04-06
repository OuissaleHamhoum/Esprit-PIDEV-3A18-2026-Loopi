<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FileUploadService
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function upload(UploadedFile $file, string $subDir, string $prefix = 'f'): string
    {
        $dir = $this->projectDir.'/public/uploads/'.$subDir;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $ext = $file->guessExtension() ?: 'bin';
        $name = $prefix.'_'.uniqid('', true).'.'.$ext;
        $file->move($dir, $name);

        return 'uploads/'.$subDir.'/'.$name;
    }

    public function uploadProfile(UploadedFile $file): string
    {
        return $this->upload($file, 'profiles', 'profile');
    }

    public function uploadProduit(UploadedFile $file): string
    {
        return $this->upload($file, 'produits', 'prod');
    }

    public function uploadCollection(UploadedFile $file): string
    {
        return $this->upload($file, 'collections', 'coll');
    }

    public function uploadEvenement(UploadedFile $file): string
    {
        return $this->upload($file, 'evenements', 'evt');
    }
}
