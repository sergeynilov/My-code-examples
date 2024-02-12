<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Vote as VoteModel;
use DateConv;
use Illuminate\Support\Facades\File;
use Spatie\Image\Image;

class VoteResource extends JsonResource
{
    public function toArray($request)
    {
        $media     = null;
        $voteMedia = $this->getFirstMedia(config('app.media_app_name'));
        if ( ! empty($voteMedia) and File::exists($voteMedia->getPath())) {
            $media['media_id'] = $voteMedia->id;
            $media['url']      = $voteMedia->getUrl();
            $imageInstance = Image::load($voteMedia->getUrl());
            $media['width']     = $imageInstance->getWidth();
            $media['height']    = $imageInstance->getHeight();
            $media['size']      = $voteMedia->size;
            $media['file_name'] = $voteMedia->file_name;
            $media['mime_type'] = $voteMedia->mime_type;
        }

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'creator_id'       => $this->creator_id,
            'creator'          => new UserResource($this->whenLoaded('creator')),
            'media'            => $media,
            'vote_category_id' => $this->vote_category_id,
            'voteCategory'     => new VoteCategoryResource($this->whenLoaded('voteCategory')),

            'status'            => $this->status,
            'status_label'      => VoteModel::getStatusLabel($this->status),
            'is_quiz'           => $this->is_quiz,
            'is_quiz_label'     => VoteModel::getIsQuizLabel($this->is_quiz),
            'is_homepage'       => $this->is_homepage,
            'is_homepage_label' => VoteModel::getIsHomepageLabel($this->is_homepage),

            'ordering'             => $this->ordering,
            'created_at'           => $this->created_at,
            'created_at_formatted' => DateConv::getFormattedDateTime($this->created_at),
            'updated_at'           => $this->updated_at,
            'updated_at_formatted' => DateConv::getFormattedDateTime($this->updated_at),
        ];
    }

}
