<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\TodoTask;
class TodoTaskRequest extends FormRequest
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
        return TodoTask::getTodoTaskValidationRulesArray( $request->get('id'), $request->get('todo_id'), [/*'todo_id'*/] );
    }

    public function messages()
    {
        return [
            'check_todo_task_unique_by_todo_id'    => 'This todo already have assigned Task with such name',
        ];
    }

}
