<?php

namespace App\Http\Requests;

use App\Rules\TaskModelRules;
use Illuminate\Foundation\Http\FormRequest;

class CreateTaskRequest extends FormRequest
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
        $taskTranslationValidationRulesArray = TaskModelRules::getValidationRulesArray();
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
