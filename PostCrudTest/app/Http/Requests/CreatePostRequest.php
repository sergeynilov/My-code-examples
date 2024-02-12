<?php

namespace App\Http\Requests;

use App\Repositories\Rules\PostModelRules;
use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
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
        $postTranslationValidationRulesArray = PostModelRules::getValidationRulesArray(
            postId: null,
            skipFieldsArray: ['post_id']
        );

        return $postTranslationValidationRulesArray;
    }

    public function messages()
    {
        return PostModelRules::getValidationMessagesArray();
    }
}
