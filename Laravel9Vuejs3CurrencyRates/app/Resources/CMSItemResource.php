<?php

namespace App\Http\Resources;

use App\Library\Services\DateFunctionalityServiceInterface;
use Illuminate\Http\Resources\Json\JsonResource;

use App;
use Illuminate\Support\Facades\File;
use Spatie\Image\Image;

class CMSItemResource extends JsonResource
{
    protected static $showDefaultImage = false;

    public function showDefaultImage($value){
        self::$showDefaultImage= $value;
        return $this;
    }


    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */

    public function toArray($request)
    {
        $dateFunctionality = App::make(DateFunctionalityServiceInterface::class);
        $CMSItemImage = [];
        $CMSItemMedia = $this->getFirstMedia(config('app.media_app_name'));

        if ( ! empty($CMSItemMedia) and File::exists($CMSItemMedia->getPath())) {
            $CMSItemImage['url']        = $CMSItemMedia->getUrl();
            $imageInstance              = Image::load($CMSItemMedia->getUrl());
            $CMSItemImage['width']      = $imageInstance->getWidth();
            $CMSItemImage['height']     = $imageInstance->getHeight();
            $CMSItemImage['size']       = $CMSItemMedia->size;
            $CMSItemImage['file_name'] = $CMSItemMedia->file_name;
            $CMSItemImage['mime_type'] = $CMSItemMedia->mime_type;
        }
        else {
            $CMSItemImage['url']  = self::$showDefaultImage ? '/images/default-article.png' : '';
        }

        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'key'                  => $this->key,
            'text'                 => $this->text,
            'author_id'            => $this->author_id,
            'author'               => new UserResource($this->whenLoaded('author')),
            'cmsItemImageProps'    => new MediaImageResource($CMSItemImage),
            'published'            => $this->published,
            'created_at'           => $this->created_at,
            'created_at_formatted' => $dateFunctionality->getFormattedDateTime($this->created_at),
            'updated_at'           => $this->updated_at,
            'updated_at_formatted' => $dateFunctionality->getFormattedDateTime($this->updated_at),
        ];
    }

    public static function customCollection($resource, $showDefaultImage): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        self::$showDefaultImage= $showDefaultImage;
        return parent::collection($resource);
    }

    public function with($request)
    {
        return [
            'meta' => [
                'version' => getAppVersion()
            ]
        ];
    }

}

