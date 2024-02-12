<?php

namespace App\Rules;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/*
Class-container for rules/messages of tasks validation
*
*/

class TaskModelRules
{

    /**
     * @param int $taskId ID of task
     * @param array<int, string> $skipFieldsArray which fields must be skipped from array
     *
     * @return array<string, string>
     */
    public static function getValidationRulesArray(int $taskId = null, array $skipFieldsArray = []): array
    {
        $taskTable = (new Task)->getTable();
        $validationRulesArray = [
            'parent_id' => 'nullable|exists:' . $taskTable . ',id',
            'user_id' => 'nullable|exists:' . ((new User)->getTable()) . ',id',
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique($taskTable)->ignore($taskId),
            ],

            'priority' => 'required|in:' . getValueLabelKeys(TaskPriority::getPrioritySelectionItems()),
            'status' => 'required|in:' . getValueLabelKeys(TaskStatus::getStatusSelectionItems()),
            'description' => 'string|required',
        ];
        foreach ($skipFieldsArray as $field) {
            if ( ! empty($validationRulesArray[$field])) {
                $validationRulesArray = Arr::except($validationRulesArray, $field);
            }
        }

        return $validationRulesArray;
    }

    /**
     * Returns custom Messages for validation errors
     *
     * @return array<string, string>
     */
    public static function getValidationMessagesArray(): array
    {
        return [
            'parent_id.invalid' => 'Parent Id is invalid. Must be valid reference to tasks table',
            'user_id.invalid' => 'User Id is invalid. Must be valid reference to users table',
        ];
    }
}
