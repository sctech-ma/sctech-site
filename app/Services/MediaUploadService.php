<?php

declare(strict_types=1);

namespace SCTech\Services;

use finfo;
use SCTech\Repositories\AdminAuditRepository;
use SCTech\Repositories\MediaRepository;
use SCTech\Validation\ValidationResult;

final class MediaUploadService
{
    /** @param array<string, string> $allowedMimes */
    public function __construct(
        private readonly MediaRepository $media,
        private readonly AdminAuditRepository $audit,
        private readonly string $uploadDirectory,
        private readonly array $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
        private readonly int $maxBytes = 5242880,
        private readonly int $maxPixels = 20000000,
    ) {
    }

    /**
     * @param array{error?:int,tmp_name?:string,name?:string,size?:int} $file
     */
    public function store(
        array $file,
        string $altText,
        string $locale,
        int $actorId,
        string $requestId,
        string $ipHash,
    ): MediaUploadResult {
        $errors = [];
        $tmp = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
            $errors['file'][] = 'Sélectionnez un fichier image valide.';
        } elseif ($size < 1 || $size > $this->maxBytes) {
            $errors['file'][] = 'Le fichier dépasse la taille autorisée.';
        }
        if (mb_strlen(trim($altText), 'UTF-8') < 2 || mb_strlen($altText, 'UTF-8') > 255) {
            $errors['alt_text'][] = 'Décrivez l’image en 2 à 255 caractères.';
        }
        if ($errors !== []) {
            return new MediaUploadResult(false, null, ValidationResult::invalid($errors));
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!is_string($mime) || !isset($this->allowedMimes[$mime])) {
            return new MediaUploadResult(false, null, ValidationResult::invalid(['file' => ['Format de fichier non autorisé.']]));
        }
        $dimensions = getimagesize($tmp);
        if (!is_array($dimensions) || ($dimensions[0] * $dimensions[1]) > $this->maxPixels) {
            return new MediaUploadResult(false, null, ValidationResult::invalid(['file' => ['Dimensions d’image invalides ou excessives.']]));
        }
        $realRoot = realpath($this->uploadDirectory);
        if ($realRoot === false || !is_dir($realRoot) || !is_writable($realRoot)) {
            return new MediaUploadResult(false, null, ValidationResult::invalid(['file' => ['Le stockage des médias est indisponible.']]));
        }

        $filename = bin2hex(random_bytes(20)) . '.' . $this->allowedMimes[$mime];
        $destination = $realRoot . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmp, $destination)) {
            return new MediaUploadResult(false, null, ValidationResult::invalid(['file' => ['Le fichier n’a pas pu être enregistré.']]));
        }
        @chmod($destination, 0644);
        try {
            $id = $this->media->create([
                'locale' => $locale,
                'content_key' => 'media.' . pathinfo($filename, PATHINFO_FILENAME),
                'path' => 'uploads/' . $filename,
                'original_name' => mb_substr(basename((string) ($file['name'] ?? 'image')), 0, 255),
                'mime_type' => $mime,
                'byte_size' => $size,
                'width' => (int) $dimensions[0],
                'height' => (int) $dimensions[1],
                'alt_text' => trim($altText),
                'caption' => null,
                'created_by' => $actorId,
            ]);
        } catch (\Throwable $exception) {
            @unlink($destination);
            throw $exception;
        }
        $this->audit->record($actorId, 'media.uploaded', 'media', $id, $requestId, $ipHash, ['mime_type' => $mime, 'byte_size' => $size]);

        return new MediaUploadResult(true, $id, ValidationResult::valid());
    }
}
