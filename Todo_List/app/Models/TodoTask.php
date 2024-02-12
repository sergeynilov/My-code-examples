<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TodoTask extends Model
{
    use HasFactory;

    protected $table = 'todo_task';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable
        = [
            'todo_id',
            'name',
            'description',
            'deadline',
            'status',
            'created_at'
        ];


    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('status', 'asc');
            $builder->orderBy('deadline', 'desc');
        });
    }

    public function scopeGetById($query, $id)
    {
        return $query->where(with(new TodoTask)->getTable() . '.id', $id);
    }

    public function scopeGetByName($query, $name = null)
    {
        if (empty($name)) {
            return $query;
        }

        return $query->where(with(new TodoTask)->getTable() . '.name', 'like', '%' . $name . '%');
    }

    private static $todoTaskStatusLabelValueArray = ['U' => 'Uncompleted', 'D' => 'Disabled', 'C' => 'Completed'];

    public static function getTodoTaskStatusValueArray($key_return = true): array
    {
        $resArray = [];
        foreach (self::$todoTaskStatusLabelValueArray as $key => $value) {
            if ($key_return) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }

        return $resArray;
    }

    public static function getTodoTaskStatusLabel(string $status): string
    {
        if ( ! empty(self::$todoTaskStatusLabelValueArray[$status])) {
            return self::$todoTaskStatusLabelValueArray[$status];
        }

        return '';
    }


    public function scopeGetByStatus($query, $status = null)
    {
        if ( ! isset($status) or strlen($status) == 0) {
            return $query;
        }

        return $query->where(with(new TodoTask)->getTable() . '.status', $status);
    }


    public function scopeGetByTodoId($query, int $todo_id = null)
    {
        if ( ! empty($todo_id)) {
            $query->where(with(new TodoTask)->getTable() . '.todo_id', $todo_id);
        }

        return $query;
    }

    public function todo()
    {
        return $this->belongsTo('App\Models\Todo', 'todo_id', 'id');
    }

    public static function getSimilarTodoTaskByTodoId( string $name, int $todo_id,  int $id = null,  $return_count = false ) {
        $quoteModel = TodoTask::where('name', $name);
        $quoteModel = $quoteModel->where('todo_id', '=', $todo_id);
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


    public static function getTodoTaskValidationRulesArray(
        $todo_task_id = null,
        $todo_id = null,
        array $skipFieldsArray = []
    ): array {

        $additional_name_validation_rule = 'check_todo_task_unique_by_todo_id:' . $todo_id . ',' . (! empty($todo_id) ? $todo_id : '');
        $validationRulesArray            = [
            'todo_id' => 'required|exists:' . (with(new Todo)->getTable()) . ',id',
            'name' => [
                'required',
                'string',
                'max:100',
                $additional_name_validation_rule
            ],
            'description' => [
                'required',
                'string'
            ],
            'deadline' => 'required|date',
            'status' => 'required|in:' . getValueLabelKeys(TodoTask::getTodoTaskStatusValueArray(false)),

        ];
        foreach ($skipFieldsArray as $next_field) {
            if ( ! empty($validationRulesArray[$next_field])) {
                unset($validationRulesArray[$next_field]);
            }
        }

        return $validationRulesArray;
    }

}

