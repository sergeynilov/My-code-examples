<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Todo;
class TodoRequest extends FormRequest
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
        $request= Request();
        return Todo::getTodoValidationRulesArray( $request->get('id'), $request->get('user_id'), ['todo_id'] );
    }

    public function messages()
    {
        return [
            'check_todo_unique_by_user_id'    => 'This user already have assigned Todo with such name',
        ];
    }

}
