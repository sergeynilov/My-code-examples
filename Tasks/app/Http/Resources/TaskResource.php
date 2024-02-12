<?php

namespace App\Http\Resources;

use App\Enums\ConfigValueEnum;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * @return array<string, string>
     */
    public function toArray($request): array
    {
        $datetimeFormat = ConfigValueEnum::get(ConfigValueEnum::DATETIME_ASTEXT_FORMAT);
        return [
            'id' => $this->id,
            'title' => $this->title,
            'priority' => $this->priority,
            'priority_label' => TaskPriority::getLabel(TaskPriority::from($this->priority)),
            'status' => $this->status,
            'status_label' => TaskStatus::getLabel(TaskStatus::from($this->status)),
            'description' => $this->description,
            'completed_at' => !empty($this->completed_at) ? (Carbon::parse($this->completed_at)) : '',
            'completed_at_formatted' => !empty($this->completed_at) ? (Carbon::parse($this->completed_at))->format($datetimeFormat) : '',

            'created_at' => (Carbon::parse($this->created_at)),
            'created_at_formatted' => (Carbon::parse($this->created_at))->format($datetimeFormat),
            'user' => new UserResource($this->whenLoaded('user')),
            'parentTask' => new TaskResource($this->whenLoaded('parentTask')),
        ];
    }
}
