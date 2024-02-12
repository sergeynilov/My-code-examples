<?php

namespace App\Http\Resources;

use App\Enums\CheckValueType;
use DateFunctionality;
use App\Models\Settings;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Page as PageModel;
use App;
use Auth;
use Illuminate\Support\Facades\File;
use Spatie\Image\Image;
use Illuminate\Support\Str;

class PageResource extends JsonResource
{

    public function toArray($request)
    {
        $pageImage    = null;
        $pageDocument = null;
        foreach ($this->getMedia(config('app.media_app_name')) as $nextMediaImage) {
            $fileType = $nextMediaImage->getCustomProperty('file_type', '');
            if ($fileType === 'cover_image') {
                if ( ! empty($nextMediaImage) and File::exists($nextMediaImage->getPath())) {
                    $pageImage['url']       = $nextMediaImage->getUrl();
                    $imageInstance          = Image::load($nextMediaImage->getUrl());
                    $pageImage['width']     = $imageInstance->getWidth();
                    $pageImage['height']    = $imageInstance->getHeight();
                    $pageImage['size']      = $nextMediaImage->size;
                    $pageImage['file_name'] = $nextMediaImage->file_name;
                    $pageImage['mime_type'] = $nextMediaImage->mime_type;
                }
            }

            if ($fileType === 'document') {
                if ( ! empty($nextMediaImage) and File::exists($nextMediaImage->getPath())) {
                    $pageDocument['url']       = $nextMediaImage->getUrl();
                    $pageDocument['size']      = $nextMediaImage->size;
                    $pageDocument['file_name'] = $nextMediaImage->file_name;
                    $pageDocument['mime_type'] = $nextMediaImage->mime_type;
                    if (Str::contains($nextMediaImage->mime_type, ['image/'],
                        true)) { // Image can have width and height
                        $imageInstance          = Image::load($nextMediaImage->getUrl());
                        $pageDocument['width']  = $imageInstance->getWidth();
                        $pageDocument['height'] = $imageInstance->getHeight();
                    }
                }
            }
        } // foreach ($this->getMedia(config('app.media_app_name')) as $nextMediaImage) {

        return [
            'id'                        => $this->id,
            'title'                     => $this->title,
            'slug'                      => $this->slug,
            'content_shortly'           => $this->content_shortly,
            'creator_id'                => $this->creator_id,
            'is_homepage'               => $this->is_homepage,
            'is_homepage_label'         => PageModel::getIsHomepageLabel($this->is_homepage),
            'published'                 => $this->published,
            'published_label'           => PageModel::getPublishedLabel($this->published),
            'price'                     => $this->price,
            'price_formatted'           => formatCurrencySum($this->price),
            'image'                     => $this->image,
            'meta_description'          => $this->meta_description,
            'meta_keywords'             => $this->meta_keywords,
            'created_at'                => $this->created_at,
            'created_at_formatted'      => DateFunctionality::getFormattedDateTime($this->created_at),
            'updated_at'                => $this->updated_at,
            'updated_at_formatted'      => DateFunctionality::getFormattedDateTime($this->updated_at),

            // Referenced data
            'pageRevisions'             => $this->when(
                optional($request->user())->id == $this->creator_id,
                fn() => PageRevisionResource::collection($this->whenLoaded('pageRevisions')),
            ),
            'pageImageProps'            => $this->when(
                ! empty($pageImage),
                fn() => new MediaImageResource($pageImage),
            ),
            'pageDocumentProps'         => $this->when(
                ! empty($pageDocument),
                fn() => new MediaImageResource($pageDocument),
            ),
            'creator'                   => new UserResource($this->whenLoaded('creator')),
            'category'                  => CategoryResource::collection($this->whenLoaded('category')),
        ];
    }

    public function with($request)
    {
        return [
            'meta' => [
                'version' => getAppVersion() . ',Ab'
            ]
        ];
    }

}
