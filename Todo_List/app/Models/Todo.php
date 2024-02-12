<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Todo extends Model
{
    use HasFactory;

    protected $table = 'todo';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable       = [
            'user_id', 'name', 'description', 'completed', 'created_at'
        ];


    public function scopeGetById($query, $id)
    {
        return $query->where(with(new Todo)->getTable() . '.id', $id);
    }

    public function scopeGetByName($query, $name = null)
    {
        if (empty($name)) {
            return $query;
        }
        return $query->where(with(new Todo)->getTable() . '.name', 'like', '%' . $name . '%');
    }

    private static $todoCompletedLabelValueArray = array(0 => 'Active', 1 => 'Completed');
    public static function getTodoCompletedValueArray($key_return = true): array
    {
        $resArray = [];
        foreach (self::$todoCompletedLabelValueArray as $key => $value) {
            if ($key_return) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }

        return $resArray;
    }

    public static function getTodoCompletedLabel(string $completed): string
    {
        if ( ! empty(self::$todoCompletedLabelValueArray[$completed])) {
            return self::$todoCompletedLabelValueArray[$completed];
        }

        return '';
    }


    public function scopeGetByCompleted($query, $completed = null)
    {
        if ( ! isset($completed) or strlen($completed) == 0) {
            return $query;
        }
        return $query->where(with(new Todo)->getTable() . '.completed', $completed);
    }


    public function scopeGetByUserId($query, int $user_id= null)
    {
        if (!empty($user_id)) {
            $query->where(with(new Todo)->getTable().'.user_id', $user_id);
        }
        return $query;
    }

    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id','id');
    }

    public function todoTasks()
    {
        return $this->hasMany('App\Models\TodoTask', 'todo_id', 'id');
    }

    public static function getSimilarTodoByUserId( string $name, int $user_id,  int $id = null,  $return_count = false ) {
        $quoteModel = Todo::where('name', $name);
        $quoteModel = $quoteModel->where('user_id', '=', $user_id);
        if ( ! empty($id)) {
            $quoteModel = $quoteModel->where('id', '!=', $id);
        }

        if ($return_count) {
            return $quoteModel->get()->count();
        }
        $retRow = $quoteModel->get();
        if (empty($retRow[0])) {
            return false;
        }

        return $retRow[0];
    }


    public static function getTodoValidationRulesArray($todo_id = null, $user_id = null, array $skipFieldsArray = []): array
    {

            $additional_name_validation_rule = 'check_todo_unique_by_user_id:' . $user_id . ',' . (! empty($todo_id) ? $todo_id : '');
            $validationRulesArray = [
//            'todo_id'     => 'required|exists:'.( with(new Todo)->getTable() ).',id',
            'name'      => [
                'required',
                'string',
                'max:100',
                $additional_name_validation_rule
            ],
            'description'  => [
                'string',
                'nullable'
            ],
            'is_top'    => 'nullable',
            'completed'    => 'boolean',

        ];
        foreach ($skipFieldsArray as $next_field) {
            if ( ! empty($validationRulesArray[$next_field])) {
                unset($validationRulesArray[$next_field]);
            }
        }

        return $validationRulesArray;
    }

}

