<?php

namespace App\Library\Services;

use App\Models\Page;
use App\Models\Author;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\UploadedFile;
use App\Http\Resources\MediaImageResource;

class ModelImageUpload
{

    private $storage = null;
    private $modelItem = null;
    private $fileToUpload = null;
    private $fileType = null;

    public function __construct(Page | Author $modelItem, string $storage = 'LocalMediaLibrary')
    {
        $this->modelItem = $modelItem;
        $this->storage = $storage;
    }

    public function uploadFile( UploadedFile|string $fileToUpload, string $fileType, string $requestName,
        bool $deleteExitingMediaFiles = false, int $maxlength = 255 ): Null | MediaImageResource
    {
        $this->fileToUpload = $fileToUpload;
        $this->fileType = $fileType;

        $retResource = null;
        if ( $this->storage === 'LocalMediaLibrary' ) {
            if ($deleteExitingMediaFiles) {
                foreach ($this->modelItem->getMedia(config('app.media_app_name')) as $pageMediaImage) {
                    $pageMediaImage->delete();
                }
            }
            if ( ! empty($this->fileToUpload)) {
                if (gettype($this->fileToUpload) === 'string') {
                    $imageFilename = basename($this->fileToUpload);
                    $imageMedia = $this->modelItem
                        ->addMediaFromUrl($this->fileToUpload)
                        ->usingFileName($imageFilename)
                        ->toMediaCollection(config('app.media_app_name'));
                    $imageMedia->setCustomProperty('file_type',$this->fileType );
                    $imageMedia->save();
                } // gettype === string

                if (gettype($this->fileToUpload) === 'object') {
                    $imageFilename = checkValidFilename($this->fileToUpload->getClientOriginalName(), $maxlength,
                        true);
                    $imageMedia = $this->modelItem
                        ->addMediaFromRequest($requestName)
                        ->usingFileName($imageFilename)
                        ->toMediaCollection(config('app.media_app_name'));
                    $imageMedia->setCustomProperty('file_type', $this->fileType);
                    $imageMedia->save();
                    $retResource = new MediaImageResource($imageMedia);
                } // gettype === 'object'
            } // if (!empty($this->fileToUpload)) {
        } // if ( $storage === 'LocalMediaLibrary' ) {
        return $retResource;
    }

}
