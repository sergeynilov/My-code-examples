<?php
namespace App\Repositories\Interfaces;

use Illuminate\Http\Request;

interface UploadedImageInterface
{
    /**
     * Upload image in storage under relative path storage/app/public/models/model-ID/image_name.ext
     *
     * @param Request $request - request with uploaded file
     *
     * @param string $imageFieldName - image key in request with uploaded file
     *
     * @return array :    result === 1 if upload was successfull, uploadedImagePath - relative path of uploaded file
     * under storage, imageName - name  of uploaded file
     */
    public function imageUpload(Request $request, string $imageFieldName): array;

    /**
     * Remove image from storage under relative path storage/app/public/models/model-ID/image_name.ext by product Id
     *
     * @param string $id - product Id
     *
     * @return void
     */
    public function imageClear(string $id): void;
}

