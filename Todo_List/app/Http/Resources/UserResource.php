<?php

namespace App\Http\Resources;

use App\Library\Services\DateFunctionalityServiceInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use App;

class UserResource extends JsonResource
{


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
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'email'                => $this->email,
            'email_verified_at'    => $this->email_verified_at,
            'created_at'           => $this->created_at,
            'created_at_formatted' => $dateFunctionality->getFormattedDateTime($this->created_at),
            'updated_at'           => $this->updated_at,
            'updated_at_formatted' => $dateFunctionality->getFormattedDateTime($this->updated_at),
        ];
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

