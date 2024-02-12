<?php

namespace App\Http\Requests;

use App\Repositories\Rules\TagModelRules;
use Illuminate\Foundation\Http\FormRequest;

class CreateTagRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $request = request();
        $tagTranslationValidationRulesArray = TagModelRules::getValidationRulesArray(
            tagId: null,
            skipFieldsArray: ['tag_id']
        );

        return $tagTranslationValidationRulesArray;
    }

    public function messages()
    {
        return TagModelRules::getValidationMessagesArray();
    }
}
