<?php

namespace App\Library\Services\Interfaces;

use Illuminate\Http\Request;

interface UploadedFileManagementInterface
{

    public function setImageFieldName(string $imageFieldName): void;

    public function setRequest(Request $request): void;

    public function validate(int $uploadImageRules): array;

    public function upload(string $uploadedImageDirectoryPath): array;

    public function uploadFromUrl(string $imageFileUrl, string $uploadedImageDirectoryPath): array;

    public function remove(string $relativeFilePath): bool;

    public function getImageFileDetails(string $itemId, string $imageFilename = null,
        string $imagesUploadDirectory = '',  bool $skipNonExistingFile = false): array;

    public function getImageProps(string $imagePath, array $imagePropsArray = []): array;

}
