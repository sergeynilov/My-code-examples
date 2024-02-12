<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MediaImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'url' => $this['url'] ?? '',
            'width' => $this['width'] ?? '',
            'height' => $this['height'] ?? '',
            'size' => $this['size'] ?? '',
            'file_name' => $this['file_name'] ?? '',
            'mime_type' => $this['mime_type'] ?? '',
        ];
    }
}


