<?php

namespace App\Http\Requests;

use App\Models\BadWord;
use Illuminate\Foundation\Http\FormRequest;

class BadWordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $request = Request();
        return BadWord::getValidationRulesArray($request->get('_id'), []);
    }

    public function messages()
    {
        return BadWord::getValidationMessagesArray();
    }

}
