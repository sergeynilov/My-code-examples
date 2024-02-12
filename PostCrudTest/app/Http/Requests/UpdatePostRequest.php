<?php

namespace App\Http\Requests;

use App\Repositories\Rules\PostModelRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
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
        $postTranslationValidationRulesArray = PostModelRules::getValidationRulesArray(
            postId: $request->post_id,
            skipFieldsArray: []
        );

        return $postTranslationValidationRulesArray;
    }

    public function messages()
    {
        return PostModelRules::getValidationMessagesArray();
    }
}
