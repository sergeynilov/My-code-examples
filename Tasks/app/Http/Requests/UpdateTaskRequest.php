<?php

namespace App\Http\Requests;

use App\Rules\TaskModelRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
    */
    public function rules(): array
    {
        $request = request();
        $taskTranslationValidationRulesArray = TaskModelRules::getValidationRulesArray(
            taskId: $request->id
        );

        return $taskTranslationValidationRulesArray;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return TaskModelRules::getValidationMessagesArray();
    }
}
