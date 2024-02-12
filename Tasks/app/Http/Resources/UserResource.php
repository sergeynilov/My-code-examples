<?php

namespace App\Http\Resources;

use App\Enums\ConfigValueEnum;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array<string, string>
     */
    public function toArray($request)
    {
        $datetimeFormat = ConfigValueEnum::get(ConfigValueEnum::DATETIME_ASTEXT_FORMAT);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,

            'created_at' => $this->created_at,
            'created_at_formatted' => (Carbon::parse($this->created_at))->format($datetimeFormat),
            'updated_at' => $this->updated_at,
            'updated_at_formatted' => (Carbon::parse($this->updated_at))->format($datetimeFormat),
        ];
    }
}
