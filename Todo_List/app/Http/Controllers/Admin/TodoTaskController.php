<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TodoTaskRequest;
use App\Http\Resources\TodoTaskResource;
use App\Models\TodoTask;
use App\Models\Todo;
use Carbon\Carbon;
use Exception;
use DB;

class TodoTaskController extends Controller
{
    public function index($todo_id)
    {
//        \Log::info(varDump($todo_id, ' -1 TodoController $todo_id::'));
//        \Log::info(varDump($filter_date_from, ' -1 TodoController $filter_date_from::'));
//        \Log::info(varDump($filter_date_till, ' -1 TodoController $filter_date_till::'));

        $request         = request();
        $filter_status   = $request->filter_status ?? '';
        $order_by        = $request->order_by ?? 'created_at';
        $order_direction = $request->order_direction ?? 'desc';

        $todoTasks = TodoTask
            ::getByTodoId((int)$todo_id)
            ->getByStatus($filter_status)
//            ->getByDay($filter_date_till, '<')
            ->orderBy($order_by, $order_direction)
            ->get();

        return (TodoTaskResource::collection($todoTasks));
    }

    //     Route::get('todo_tasks/{todo_id}/{todo_task_id}', [TodoTaskController::class, 'details'])->name('todo_tasks.index.details');
    public function details($todo_id, $todo_task_id)
    {
        \Log::info(varDump($todo_id, ' -1 TodoController $todo_id::'));
        \Log::info(varDump($todo_task_id, ' -1 TodoController $todo_task_id::'));
//        \Log::info(varDump($filter_date_from, ' -1 TodoController $filter_date_from::'));
//        \Log::info(varDump($filter_date_till, ' -1 TodoController $filter_date_till::'));

        $todoTask = TodoTask
            ::getByTodoId((int)$todo_id)
            ->getById((int)$todo_task_id)
//            ->getByDay($filter_date_till, '<')
            ->first();
        if ($todoTask == null) {
            return response()->json([
                'message' => 'Task # "' . $todo_task_id . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        return new TodoTaskResource($todoTask);
    }

    public function destroy($todo_task_id)
    {

        $todoTask = TodoTask::find($todo_task_id);
        if ($todoTask == null) {
            \Log::info(varDump(-1000, ' -1000 destroy  $todo_task_id::'));

            return response()->json([
                'message' => 'Task # "' . $todo_task_id . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }
        try {
            DB::beginTransaction();
            $todoTask->delete();
            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->noContent();
    }

    public function update(TodoTaskRequest $request, $todo_id)
    {
        \Log::info('-1 update $todo_id::' . print_r($todo_id, true));
        \Log::info('-1 update $request->all()::' . print_r($request->all(), true));
        $todo_task_id = $request->id;

        $todo = Todo
            ::getById($todo_id)
            ->first();
        \Log::info(varDump($todo, ' -1 $todo::'));
        if (empty($todo)) {
            \Log::info(' -Error 1 ::');

            return redirect()->back()->withErrors([
                'message' => 'Todo # "' . $todo_id . '" not found',
            ]);
//            return response()->json([
//                'message' => 'Todo # "' . $todo_id . '" not found',
//            ], HTTP_RESPONSE_NOT_FOUND);
        }

        $todoTask = TodoTask
            ::getById($todo_task_id)
            ->first();
        if (empty($todoTask)) {
            \Log::info(' -Error 2 ::');

            return redirect()->back()->withErrors([
                'message' => 'Task # "' . $todo_task_id . '" not found',
            ]);
        }

        try {
            DB::beginTransaction();

            $todoTask->name        = $request->name;
            $todoTask->description = $request->description;
            $todoTask->status      = $request->status;
            $todoTask->deadline    = str_replace('T00:00:00.000Z', '', $request->deadline);
            $todoTask->updated_at  = Carbon::now(config('app.timezone'));
            $todoTask->save();
            $todo->updated_at = Carbon::now(config('app.timezone'));
            $todo->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            \Log::info('-1 TodoController store $e->getMessage() ::' . print_r($e->getMessage(), true));

            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', '');
    } // public function update($todo_id)

    public function store(TodoTaskRequest $request, $todo_id)
    {
        \Log::info('-1 store $todo_id::' . print_r($todo_id, true));
        \Log::info('-1 store $request->all()::' . print_r($request->all(), true));

        $todo = Todo
            ::getById($todo_id)
            ->first();
        \Log::info(varDump($todo, ' -1 $todo::'));
        if (empty($todo)) {
            \Log::info(' -Error 1 ::');

            return redirect()->back()->withErrors([
                'message' => 'Todo # "' . $todo_id . '" not found',
            ]);
        }

        try {
            DB::beginTransaction();
            $todoTask              = new TodoTask();
            $todoTask->todo_id     = $request->todo_id;
            $todoTask->name        = $request->name;
            $todoTask->description = $request->description;
            $todoTask->status    = $request->status;
            $todoTask->deadline    = getParsedDate($request->deadline);
            $todoTask->save();

            $todo->updated_at = Carbon::now(config('app.timezone'));
            $todo->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            \Log::info('-1 TodoController store $e->getMessage() ::' . print_r($e->getMessage(), true));

            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', '');
    } // public function store($todo_id)


}
