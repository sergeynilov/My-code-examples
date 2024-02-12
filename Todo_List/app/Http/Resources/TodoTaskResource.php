<?php

namespace App\Http\Resources;

use App\Library\Services\DateFunctionalityServiceInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\TodoTask;
use Carbon\Carbon;
use App;

class TodoTaskResource extends JsonResource
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

        $is_task_expired = !empty($this->deadline) ? Carbon::parse($this->deadline)->isPast() : false;

        return [
            'id'      => $this->id,
            'todo_id' => $this->todo_id,
            'name'    => $this->name,

            'deadline'           => $this->deadline,
            'deadline_formatted' => $dateFunctionality->getFormattedDate($this->deadline),

            'is_task_expired' => $is_task_expired,
            'description'     => $this->description,
            'status'          => $this->status,
            'status_label'    => \App\Models\TodoTask::getTodoTaskStatusLabel($this->status),

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

