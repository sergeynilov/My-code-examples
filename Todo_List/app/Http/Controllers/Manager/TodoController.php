<?php

namespace App\Http\Controllers\Manager;

use App\Http\Resources\TodoResource;
use App\Models\Todo;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
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
        return Inertia::render('Manager/Todo/Index', [ ]);
    }

    public function filter()
    {

        $request                = request();
        $backend_items_per_page = 10;

        $page            = $request->page ?? 1;
        $filter_name     = $request->filter_name ?? '';

        $todos = Todo
            ::getByName($filter_name)
            ->with('user')
            ->with('todoTasks')
            ->orderBy('completed', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($backend_items_per_page, array('*'), 'page', $page);
        return (TodoResource::collection($todos));
    } // public function filter()


}
