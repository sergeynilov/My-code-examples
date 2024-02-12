<?php

namespace App\Library\Services;

use App\Library\Services\Interfaces\UploadedFileManagementInterface;
use App\Library\Rules\RulesImageUploading;
use Exception;
use AppContent;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AwsS3StorageUploadedFileManagement implements UploadedFileManagementInterface
{
    private string $imageFieldName;
    private Request $request;
    private string $defaultImageNameExtension = '.jpg';

    /**
     * Set field name of file input - used in file uploading requests
     *
     * @param string $imageFieldName
     *
     * @return void
     */
    public function setImageFieldName(string $imageFieldName): void
    {
        $this->imageFieldName = $imageFieldName;
    }

    /**
     * Set request with uploaded file ($this->imageFieldName)
     *
     * @param string $imageFieldName
     *
     * @return void
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Validate if uploaded file pass validations by parameters in config/app.php
     *
     * @param int $uploadImageRules - which validation rules must be used from config/app.php
     *
     * @return array[ bool result - if validation was successful, uploadedImagePath - relative path of uploaded image
     * under storage, imageName - name of uploaded file, message - error message if uploading failed
     */
    public function validate(int $uploadImageRules): array
    {
        // get validation rules from config/app.php by $uploadImageRules
        $imageUploadingRules = new RulesImageUploading($uploadImageRules);
        $imageRules          = $imageUploadingRules->getRules();

        $validator = \Validator::make($this->request->all(), [$this->imageFieldName => $imageRules], []);
        if ($validator->fails()) { // validation failed
            $errorMsg = $validator->getMessageBag()->toArray();
            $errorMessage = Arr::join($errorMsg[$this->imageFieldName], ' ');

            return ['result' => false, 'message' => $errorMessage];
        }

        return ['result' => true, 'message' => ''];
    }

    /**
     * Upload requested file ($this->request[$this->imageFieldName])
     *
     * @param string $uploadedImageDirectoryPath - directory image would be uploaded under storage path
     * in format /storage/app/public/models/model-ModelId"
     *
     * @return array[ bool result - if uploading was successful, uploadedImagePath - relative path of uploaded image
     * under storage, imageName - name of uploaded file, message - error message if uploading failed
     */
    public function upload(string $uploadedImageDirectoryPath): array
    {
        $uploadedImagePath          = '';
        $imageName                  = '';
        $awsRootDirectory           = config('app.aws_root_directory', 'APP_ROOT');
        $uploadedImageDirectoryPath = $awsRootDirectory . DIRECTORY_SEPARATOR . $uploadedImageDirectoryPath;
        try {
            $uploadedFile = $this->request->file($this->imageFieldName);

            if ( ! empty($uploadedFile)) {
                $imageName     = $this->request->image_filename;
                $imageFilePath = $uploadedFile->getPathName();
                if ( ! empty($imageName)) {
                    $uploadedImagePath = $uploadedImageDirectoryPath . $imageName;
                    Storage::disk('s3')->put($uploadedImagePath, File::get($imageFilePath));
                } // if ( !empty($imageName) ) {
            }
        } catch (Exception $e) { //
            return [
                'result'            => false,
                'uploadedImagePath' => null,
                'imageName'         => null,
                'message'           => $e->getMessage()
            ];
        }

        return ['result' => true, 'uploadedImagePath' => $uploadedImagePath, 'imageName' => $imageName];
    }

    /**
     * Upload file by absolute url
     *
     * $imageFileUrl - absolute url of image file
     *
     * @param string $uploadedImageDirectoryPath - directory image would be uploaded under storage path
     * in format /storage/app/public/models/model-ModelId"
     *
     * @return array[ bool result - if uploading was successful, uploadedImagePath - relative path of uploaded image
     * under storage, imageName - name of uploaded file, message - error message if uploading failed
     */
    public function uploadFromUrl(string $imageFileUrl, string $uploadedImageDirectoryPath): array
    {
        $awsRootDirectory  = config('app.aws_root_directory', 'APP_ROOT');
        $uploadedImagePath = '';
        $imageName         = '';
        $uploadedImageDirectoryPath = $awsRootDirectory . DIRECTORY_SEPARATOR . $uploadedImageDirectoryPath;
        try {
            $imageToUploadInfo = pathinfo($uploadedImageDirectoryPath);
            $imageNameExtension = $imageToUploadInfo['extension'] ?? '';
            $imageName          = $imageToUploadInfo['extension'] ?? '';

            $uploadedImagePath = $uploadedImageDirectoryPath . $imageName;
            if (empty($imageNameExtension)) {
                $uploadedImagePath = $uploadedImagePath . $this->defaultImageNameExtension;
                $imageName         = ($imageToUploadInfo['basename'] ?? Str::random(10)) . $this->defaultImageNameExtension;
            }
            Storage::disk('s3')->put($uploadedImagePath, file_get_contents($imageFileUrl));
        } catch (Exception $e) { //

            return [
                'result'            => false,
                'uploadedImagePath' => null,
                'imageName'         => null,
                'message'           => $e->getMessage()
            ];
        }

        return ['result' => true, 'uploadedImagePath' => $uploadedImagePath, 'imageName' => $imageName];
    }

    /**
     * Remove file from storage - if directory is empty after file deletion - it is deleted too
     *
     * @param string $relativeFilePath - file with relative path which must be deleted from storage
     * in format /storage/app/public/models/model-ModelId"
     *
     * @return bool - if deletion was successful
     */
    public function remove(string $relativeFilePath): bool
    {
        $awsRootDirectory = config('app.aws_root_directory', 'APP_ROOT');
        $relativeFilePath = $awsRootDirectory . DIRECTORY_SEPARATOR . $relativeFilePath;

        Storage::disk('s3')->delete($relativeFilePath);
        return true;
    }

    /**
     * get image file details - with image url and info details. If image was not found default ap image is returned
     *
     * @param string $relativeFilePath - file with relative path which must be deleted from storage
     * in format /storage/app/public/models/model-ModelId"
     *
     * @return bool - if deletion was successful
     */
    public function getImageFileDetails(
        string $itemId,
        string $imageFilename = null,
        string $imagesUploadDirectory = '',
        bool $skipNonExistingFile = false
    ): array {
        if (empty($imageFilename) and $skipNonExistingFile) {
            return [];
        }

        $awsRootDirectory      = config('app.aws_root_directory', 'APP_ROOT');
        $imagesUploadDirectory = $awsRootDirectory . DIRECTORY_SEPARATOR . $imagesUploadDirectory . $itemId;
        $imageFileFull = Storage::disk('s3')->url($imagesUploadDirectory . DIRECTORY_SEPARATOR . $imageFilename);

        $fileExists = true;
        try {
            Storage::disk('s3')->response($imagesUploadDirectory . DIRECTORY_SEPARATOR . $imageFilename);
        } catch (Exception $e) {
            $fileExists = false;
        }
        if ( ! $fileExists) {  // if file on AWS/S3 storage was not found
            if ($skipNonExistingFile) {
                return [];
            }
            $appRootUrl    = config('app.url');
            $imageFileFull = AppContent::EMPTY_IMAGE; // get default image
            $imageFilename = getFilenameBasename($imageFileFull);
            $imageProps    = [
                'image'     => $imageFilename,
                'file_path' => $imageFileFull,
                'image_url' => $appRootUrl . $imageFileFull
            ];
        } else {
            $imageSize         = Storage::disk('s3')->size($imagesUploadDirectory . DIRECTORY_SEPARATOR . $imageFilename);
            $imageLastModified = Storage::disk('s3')->lastModified($imagesUploadDirectory . DIRECTORY_SEPARATOR . $imageFilename);
            $tempImageProps    = $this->getImageProps($imageFileFull, []);

            $imageProps = [
                'image_name'         => $imageFilename,
                'image_url'          => $imageFileFull,
                'file_path'          => $imagesUploadDirectory . DIRECTORY_SEPARATOR . $imageFilename,
                'file_size'          => $imageSize,
                'file_size_label'    => getFileSizeAsString($imageSize),
                'file_last_modified' => $imageLastModified,
                'file_width'         => $tempImageProps['file_width'] ? $tempImageProps['file_width'] . 'px' : null,
                'file_height'        => $tempImageProps['file_height'] ? $tempImageProps['file_height'] . 'px' : null,
            ];
        }

        return $imageProps;

    }

    public function getImageProps(string $imagePath, array $imagePropsArray = []): array
    {
        $imagesUploadSource = config('app.images_source', 's3');

        if (Storage::disk(strtolower($imagesUploadSource))->exists($imagePath)) {
            return [];
        }
        $fileWidth             = Image::make($imagePath)->width();
        $fileHeight            = Image::make($imagePath)->height();
        $fileSize              = Image::make($imagePath)->filesize();
        $fileSizeLabel         = getFileSizeAsString($fileSize);
        $retArray              = [];
        $retArray['file_info'] = '<b>' . basename($imagePath) . '</b>, ' . $fileSizeLabel;

        foreach ($imagePropsArray as $nextImageProp => $nextImagePropValue) {
            $retArray[$nextImageProp] = $nextImagePropValue;
        }
        $retArray['file_size']       = $fileSize;
        $retArray['file_size_label'] = $fileSizeLabel;
        if (isset($fileWidth)) {
            $retArray['file_width'] = $fileWidth;
        }
        if (isset($fileHeight)) {
            $retArray['file_height'] = $fileHeight;
        }
        if ( ! empty($retArray['file_width']) and ! empty($retArray['file_height'])) {
            $retArray['file_info'] .= ', ' . $retArray['file_width'] . 'x' . $retArray['file_height'];
        }

        return $retArray;

    }

}
