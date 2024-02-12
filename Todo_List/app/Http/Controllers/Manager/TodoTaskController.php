<?php

namespace App\Http\Controllers\Manager;

use App\Http\Resources\TodoResource;
use App\Models\Todo;
use App\Models\TodoTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Exception;
use DB;

class TodoTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Manager/Todo/Index', [
        ]);
    }

    public function filter()
    {
        $request                = request();
        $backend_items_per_page = 10;

        $page            = $request->page ?? 1;
        $filter_name     = $request->filter_name ?? '';
        $order_by        = $request->order_by ?? 'name';
        $order_direction = $request->order_direction ?? 'desc';

        $todos = Todo
            ::getByName($filter_name)
            ->with('user')
            ->with('todoTasks')
            ->orderBy($order_by, $order_direction)
            ->paginate($backend_items_per_page, array('*'), 'page', $page);

        return (TodoResource::collection($todos));
    } // public function filter()


    public function complete(Request $request, int $todo_id, int $todo_task_id)
    {
        $todo = Todo
            ::getById($todo_id)
            ->first();
        if (empty($todo)) {
            return response()->json([
                'message' => 'Todo # "' . $todo_id . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        $todoTask = TodoTask
            ::getById($todo_task_id)
            ->first();
        if (empty($todoTask)) {
            return response()->json([
                'message' => 'Task # "' . $todo_task_id . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        $is_task_expired = Carbon::parse($todoTask->deadline)->isPast();
        if ($is_task_expired) {
            return response()->json([
                'message' => 'Task is expired',
            ], HTTP_RESPONSE_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();
            $todoTask->status     = 'C'; // Completed
            $todoTask->updated_at = Carbon::now(config('app.timezone'));
            $todoTask->save();

            $uncompleted_todo_tasks_count = TodoTask
                ::getByTodoId($todo_task_id)
                ->getByStatus('U') // 'U=>Uncompleted
                ->count();
            if ($uncompleted_todo_tasks_count === 0) {
                $todo->completed  = true;
                $todo->updated_at = Carbon::now(config('app.timezone'));
                $todo->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'todo'    => $todo,
            'message' => 'Task was successfully completed',
        ], HTTP_RESPONSE_OK);
    } // public function complete(Request $request, int $todo_id)


    public function uncomplete(Request $request, int $todo_id, int $todo_task_id)
    {
        $todo = Todo
            ::getById($todo_id)
            ->first();
        if (empty($todo)) {
            return response()->json([
                'message' => 'Todo # "' . $todo_id . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        $todoTask = TodoTask
            ::getById($todo_task_id)
            ->first();
        if (empty($todoTask)) {
            return response()->json([
                'message' => 'Task # "' . $todo_task_id . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        if ($todoTask->status != 'C') {
            return response()->json([
                'message' => 'Task # "' . $todo_task_id . '" is not Completed',
            ], HTTP_RESPONSE_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();
            $todoTask->status     = 'U'; // 'U=>Uncompleted
            $todoTask->updated_at = Carbon::now(config('app.timezone'));
            $todoTask->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'todo'    => $todo,
            'message' => 'Task was successfully uncompleted',
        ], HTTP_RESPONSE_OK);
    } // public function uncomplete(Request $request, int $todo_id)

    public function disable(Request $request, int $todo_id, int $todo_task_id)
    {
        $todo = Todo
            ::getById($todo_id)
            ->first();
        if (empty($todo)) {
            return response()->json([
                'message' => 'Todo # "' . $todo_id . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        $todoTask = TodoTask
            ::getById($todo_task_id)
            ->first();
        if (empty($todoTask)) {
            return response()->json([
                'message' => 'Task # "' . $todo_task_id . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        try {
            DB::beginTransaction();
            $todoTask->status     = 'D'; // D=>Disabled
            $todoTask->updated_at = Carbon::now(config('app.timezone'));
            $todoTask->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'todo'    => $todo,
            'message' => 'Task was successfully disabled',
        ], HTTP_RESPONSE_OK);
    } // public function disable(Request $request, int $todo_id)

}

