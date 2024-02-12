<?php

namespace App\Http\Resources;

use App\Library\Services\DateFunctionalityServiceInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use App;
use Illuminate\Support\Facades\File;

class TodoResource extends JsonResource
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
            'user_id'              => $this->user_id,
            'user'                 => new UserResource($this->whenLoaded('user')),
            'name'                 => $this->name,
            'description'          => $this->description,
            'completed'            => $this->completed,
            'completed_label'      => \App\Models\Todo::getTodoCompletedLabel($this->completed),
            'todo_tasks_count'     => $this->when(isset($this->todo_tasks_count), $this->todo_tasks_count),
            'todoTasks'            => TodoTaskResource::collection($this->whenLoaded('todoTasks')),
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


