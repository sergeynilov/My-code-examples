<?php

namespace App\Http\Resources;

use App\Library\Services\DateFunctionalityServiceInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use App;

class LatestCurrencyHistoryResource extends JsonResource
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
            'id'        => $this->id,
            'day'       => $this->day,
            'day_label' => $dateFunctionality->getFormattedDate($this->day),

            'currency_id'          => $this->currency_id,
            'nominal'              => $this->nominal,
            'value'                => $this->value,
            'created_at'           => $this->created_at,
            'created_at_formatted' => $dateFunctionality->getFormattedDateTime($this->created_at),
        ];
    }
}
