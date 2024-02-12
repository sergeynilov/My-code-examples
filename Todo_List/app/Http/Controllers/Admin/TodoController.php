<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\TodoResource;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\TodoRequest;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Exception;
use DB;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        \Log::info(varDump(-1, ' -1 TodoController ::'));

        return Inertia::render('Admin/Todo/Index', [
        ]);
        //         return (ContactUsResource::collection($ContactUs, false));
    }

    public function filter()
    {
        \Log::info(  varDump(-9, ' -9 filter::') );

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

    public function edit($todo_id)
    {
        $todo = Todo
            ::getById($todo_id)
            ->with('user')
            ->first();
        if ($todo == null) {
            return redirect(route('admin.dashboard.index'))
                ->with('flash', 'Todo was not found')
                ->with('flash_type', 'error');
        }

        return Inertia::render('Admin/Todo/Edit', [
            'todo' => (new TodoResource($todo)),
        ]);
    }

    public function update(TodoRequest $request, $todo_id)
    {
        $todo = Todo
            ::getById($todo_id)
            ->first();

        try {
            DB::beginTransaction();

            $todo->name       = $request->name;
            $todo->description       = $request->description;
            $todo->completed     = $request->completed ? true : false;
            $todo->updated_at = Carbon::now(config('app.timezone'));
            $todo->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            \Log::info('-1 TodoController store $e->getMessage() ::' . print_r($e->getMessage(), true));

            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect(route('admin.todos.index'))
            ->with('flash', 'Todo was successfully updated')
            ->with('flash_type', 'success');
    }

    public function destroy($todo_id)
    {
        $todo = Todo::find($todo_id);
        if ($todo == null) {
            \Log::info(varDump(-1000, ' -1000 destroy  $todo_id::'));

            return response()->json([
                'message' => 'Todo # "' . $todo_id . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }
        try {
            DB::beginTransaction();
            $todo->delete();
            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();

//            \Log::info('-1 TodoController destroy $e->getMessage() ::' . print_r($e->getMessage(), true));
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect(route('admin.todos.index'))
            ->with('flash', 'You have deleted todo successfully')
            ->with('flash_type', 'success');
    }

/*    public function activate(Request $request, int $todo_id)
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to todos activate method')
                ->with('flash_type', 'error');
        }

        $todo = Todo
            ::getById($todo_id)
            ->first();
        if(empty($todo)) {
            return response()->json([
                'message' => 'Todo # "' . $todo_id . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        try {
            DB::beginTransaction();
            $todo->active     = 1;
            $todo->updated_at = Carbon::now(config('app.timezone'));
            $todo->save();
//            \Log::info('-1 ACTIVATED $todo_id::' . print_r($todo_id, true));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
//            \Log::info('-1 TodoController store $e->getMessage() ::' . print_r($e->getMessage(), true));
            return response()->json([
                'message' => $e->getMessage(),
            ], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }
        return response()->json([
            'todo' => $todo,
            'message' => 'Todo was successfully activated',
        ], HTTP_RESPONSE_OK);
    } // public function activate(Request $request, int $todo_id)

    public function deactivate(Request $request, int $todo_id)
    {
        \Log::info('-1 deactivate $todo_id::' . print_r($todo_id, true));

        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to todos deactivate method')
                ->with('flash_type', 'error');
        }

        $todo = Todo
            ::getById($todo_id)
            ->first();
        if(empty($todo)) {
            return response()->json([
                'message' => 'Todo # "' . $todo_id . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        try {
            DB::beginTransaction();
            $todo->active     = 0;
            $todo->updated_at = Carbon::now(config('app.timezone'));
            $todo->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'todo' => $todo,
            'message' => 'Todo was successfully deactivated',
        ], HTTP_RESPONSE_OK);
    } // public function deactivate(Request $request, int $todo_id)*/

}
