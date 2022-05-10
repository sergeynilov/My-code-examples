<?php

namespace App\Http\Controllers\API;

use DB;
use Exception;
use ImageOptimizer;

use App\Event;
use App\Task;
use App\Settings;
use Carbon\Carbon;
use App\TaskAssignedToUser;

use App\TaskRequireSkill;
use App\EventUser;
use App\library\CheckValueType;
use App\Facades\MyFuncsClass;
use App\Http\Resources\TaskCollection;
use App\Http\Controllers\Controller;

class TasksController extends Controller
{
    private $requestData;

    public function __construct()
    {
        $request           = request();
        $this->requestData = $request->all();
    }

    public function tasks_search() // show pages listing of tasks with filter applied
    {
        $order_by = 'price';
        $order_by_direction = 'desc';

        $viewedTasks        = ['A', 'P', 'K']; // D => Draft, A=>Assigning, C => Cancelled, P => Processing, K=> Checking, O=> Completed

        $taskList = Task
            ::getByStatus($viewedTasks)
            ->getByName( $this->requestData['search_string'] ?? '' )
            ->getByCategoryId($this->requestData['selectedCategories'])
            ->getByPrice($this->requestData['price_min'], '>= ')
            ->getByPrice($this->requestData['price_max'], '<= ')
            ->orderBy('tasks.' . $order_by, $order_by_direction)
            ->select(
                'tasks.id',
                'tasks.name',
                'tasks.price',
                'tasks.priority',
                'tasks.slug'
            )
            ->get();

        return (new TaskCollection($taskList));
    } // public function tasks_search()

    public function filter($page = null, $order_by = 'price', $filter = null) // show pages listing of tasks with filter applied
    {
        $tasks_per_page     = Settings::getValue('tasks_per_page', CheckValueType::cvtInteger, 4);
        $order_by_direction = 'asc';
        if ($order_by == 'price' or $order_by == 'priority') {
            $order_by_direction = 'desc';
        }
        $is_homepage = null;
        if (strtolower($filter) == 'is_homepage') {
            $is_homepage = true;
        }
        $limit_start = ($page - 1) * $tasks_per_page;

        $viewedTasks       = ['A', 'P', 'K']; // D => Draft, A=>Assigning, C => Cancelled, P => Processing, K=> Checking, O=> Completed
        $tasks_total_count = Task
            ::getByIsHomepage($is_homepage)
            ->getByStatus($viewedTasks)
            ->count();

        $taskList = Task
            ::getByIsHomepage($is_homepage)
            ->getByStatus($viewedTasks)
            ->leftJoin('users', 'users.id', '=', 'tasks.leader_id')
            ->leftJoin('categories', 'categories.id', '=', 'tasks.category_id')
            ->orderBy('tasks.' . $order_by, $order_by_direction)
            ->select(
                'tasks.*',
                'users.name as leader_name',
                'categories.name as category_name',
                'categories.slug as category_slug'
            )->addSelect([
                'events_count' => Event
                    ::selectRaw('count(*)')
                    ->whereColumn('events.task_id', 'tasks.id')
            ])
            ->offset($limit_start)
            ->take($tasks_per_page)
            ->get()
            ->map(function ($item) {
                $taskRequireSkills    = [];
                $tmpTaskRequireSkills = TaskRequireSkill
                    ::getByTaskId($item->id)
                    ->leftJoin('skills', 'skills.id', '=', 'task_require_skills.skill_id')
                    ->orderBy('ordering', 'desc')
                    ->select(
                        'task_require_skills.id',
                        'task_require_skills.skill_id',
                        'task_require_skills.ordering',
                        'task_require_skills.created_at',
                        'skills.name as skill_name'
                    )
                    ->get();
                foreach ($tmpTaskRequireSkills as $nextTmpTaskRequireSkill) {
                    $taskRequireSkills[] = [
                        'id'         => $nextTmpTaskRequireSkill->id,
                        'skill_id'   => $nextTmpTaskRequireSkill->skill_id,
                        'skill_name' =>
                            $nextTmpTaskRequireSkill->skill_name,
                        'ordering'   => $nextTmpTaskRequireSkill->ordering,
                        'created_at'   => $nextTmpTaskRequireSkill->created_at
                    ];
                }
                $item['taskRequireSkills'] = $taskRequireSkills;

                $filenameData = Task::setTaskImageProps($item->id, $item->image, true);

                if ( ! empty($filenameData)) {
                    $item['filenameData'] = $filenameData;
                }

                return $item;
            })
            ->all();

        return (new TaskCollection($taskList))
            ->additional([
                'meta' => [
                    'tasks_total_count' => $tasks_total_count,
                ]
            ]);
    } // public function filter($page = null, $filter= null) // show pages listing of tasks with filter applied



    public function get_tasks_by_category_id($category_id, $page = null)
    {
        $order_by           = 'price';
        $tasks_per_page     = Settings::getValue('tasks_per_page', CheckValueType::cvtInteger, 4);
        $category_id        = (int)$category_id;
        $order_by_direction = 'asc';
        $limit_start        = ($page - 1) * $tasks_per_page;

        $tasks_total_count = Task
            ::getByCategoryId($category_id)
            ->count();
        $tasksList         = Task
            ::getByCategoryId($category_id)
            ->leftJoin('users', 'users.id', '=', 'tasks.leader_id')
            ->orderBy('tasks.' . $order_by, $order_by_direction)
            ->select(
                'tasks.*',
                'users.name as leader_name'
            )->addSelect([
                'events_count' => Event
                    ::selectRaw('count(*)')
                    ->whereColumn('events.task_id', 'tasks.id')
            ])
            ->offset($limit_start)
            ->take($tasks_per_page)
            ->get()
            ->map(function ($item) {
                $filenameData = Task::setTaskImageProps($item->id, $item->image, true);
                if ( ! empty($filenameData)) {
                    $item['filenameData'] = $filenameData;
                }

                return $item;
            })
            ->all();

        return (new TaskCollection($tasksList))
            ->additional([
                'meta' => [
                    'tasks_total_count' => $tasks_total_count,
                ]
            ]);

    } // public function get_tasks_by_category_id($page = null, $filter= null) // show pages listing of tasks with filter applied


    public function index($page = null) // show pages listing of tasks
    {
        $prefix         = DB::getTablePrefix();
        $tasks_per_page = Settings::getValue('tasks_per_page', CheckValueType::cvtInteger, 2);
        $limit_start    = ($page - 1) * $tasks_per_page;

        $viewedTasks       = ['A', 'P', 'K']; // D => Draft, A=>Assigning, C => Cancelled, P => Processing, K=> Checking, O=> Completed
        $tasks_total_count = Task
            ::getByStatus($viewedTasks)
            ->count();
        $tasksList         = Task
            ::getByStatus($viewedTasks)
            ->leftJoin('users', 'users.id', '=', 'tasks.leader_id')
            ->leftJoin('categories', 'categories.id', '=', 'tasks.category_id')
            ->orderBy('tasks.date_start', 'desc')
            ->select(
                'tasks.id',
                'tasks.name',
                'tasks.description',
                'tasks.slug',
                'tasks.price',
                'tasks.creator_id',
                'tasks.leader_id',
                'tasks.category_id',
                'tasks.is_homepage',
                'tasks.priority',
                'tasks.status',
                'tasks.date_start',
                'tasks.date_end',
                'tasks.needs_reports',
                'tasks.image',
                'tasks.created_at',
                'users.name as leader_name',
                'categories.name as category_name',
                'categories.slug as category_slug',
                \DB::raw(' ( select count(' . $prefix . 'events.id) from ' . $prefix . 'events where ' . $prefix . 'events.task_id = ' .
                         $prefix . 'tasks.id ) as events_count')

            )
            ->offset($limit_start)
            ->take($tasks_per_page)
            ->get()
            ->map(function ($item) {
                $taskRequireSkills    = [];
                $tmpTaskRequireSkills = TaskRequireSkill
                    ::getByTaskId($item->id)
                    ->leftJoin('skills', 'skills.id', '=', 'task_require_skills.skill_id')
                    ->orderBy('ordering', 'desc')
                    ->select(
                        'task_require_skills.id',
                        'task_require_skills.skill_id',
                        'task_require_skills.ordering',
                        'skills.name as skill_name'
                    )
                    ->get();
                foreach ($tmpTaskRequireSkills as $nextTmpTaskRequireSkill) {
                    $taskRequireSkills[] = [
                        'id'         => $nextTmpTaskRequireSkill->id,
                        'skill_id'   => $nextTmpTaskRequireSkill->skill_id,
                        'skill_name' => $nextTmpTaskRequireSkill->skill_name,
                        'ordering'   => $nextTmpTaskRequireSkill->ordering
                    ];
                }
                $item['taskRequireSkills'] = $taskRequireSkills;
                $filenameData              = Task::setTaskImageProps($item->id, $item->image, true);
                if ( ! empty($filenameData)) {
                    $item['filenameData'] = $filenameData;
                }

                return $item;
            })
            ->all();

        return (new TaskCollection($tasksList))
            ->additional([
                'meta' => [
                    'tasks_total_count' => $tasks_total_count,
                ]
            ]);

    } // public function index($page = null) // show pages listing of tasks

    public function get_task_by_slug($task_slug) // show 1 task by slug
    {
        try {
            $task = Task
                ::getBySlug($task_slug)
                ->leftJoin('users', 'users.id', '=', 'tasks.leader_id')
                ->leftJoin('categories', 'categories.id', '=', 'tasks.category_id')
                ->orderBy('tasks.created_at', 'desc')
                ->select(
                    'tasks.id',
                    'tasks.name',
                    'tasks.description',
                    'tasks.slug',
                    'tasks.price',
                    'tasks.creator_id',
                    'tasks.leader_id',
                    'tasks.category_id',
                    'tasks.is_homepage',
                    'tasks.priority',
                    'tasks.status',
                    'tasks.date_start',
                    'tasks.date_end',
                    'tasks.needs_reports',
                    'tasks.image',
                    'tasks.created_at',
                    'users.name as leader_name',
                    'categories.name as category_name',
                    'categories.slug as category_slug'
                )
                ->first();

            if ($task === null) {
                return response()->json([
                    'message' => 'Task slug "' . $task_slug . '" not found !',
                    'task'    => null
                ], HTTP_RESPONSE_NOT_FOUND);
            }

            $filenameData = Task::setTaskImageProps($task->id, $task->image, true);
            if ( ! empty($filenameData)) {
                $task['filenameData'] = $filenameData;
            }

            $events       = Event
                ::getByTaskId($task->id)
                ->orderBy('at_time', 'desc')
                ->get()
                ->map(function ($item) {
                    $a                         = Carbon::createFromTimestamp(strtotime($item->at_time));
                    $item['is_past']           = $a->isPast();
                    $item['event_users_count'] = EventUser
                        ::getByEventId($item->id)
                        ->count();

                    return $item;
                })
                ->all();
            $task->events = $events;

            $taskRequireSkills    = [];
            $tmpTaskRequireSkills = TaskRequireSkill
                ::getByTaskId($task->id)
                ->leftJoin('skills', 'skills.id', '=', 'task_require_skills.skill_id')
                ->orderBy('ordering', 'desc')
                ->select(
                    'task_require_skills.id',
                    'task_require_skills.skill_id',
                    'task_require_skills.ordering',
                    'skills.name as skill_name'
                )
                ->get();

            foreach ($tmpTaskRequireSkills as $nextTmpTaskRequireSkill) {
                $taskRequireSkills[] = [
                    'id'         => $nextTmpTaskRequireSkill->id,
                    'skill_id'   => $nextTmpTaskRequireSkill->skill_id,
                    'skill_name' => $nextTmpTaskRequireSkill->skill_name,
                    'ordering'   => $nextTmpTaskRequireSkill->ordering
                ];
            }
            $task->taskRequireSkills = $taskRequireSkills;

            $task_assigned_to_users_count = TaskAssignedToUser
                ::getByTaskId($task->id)
                ->count();
        } catch (Exception $e) {
            return response()->json([
                'message'                      => $e->getMessage(),
                'task'                         => null,
                'task_assigned_to_users_count' => 0,
            ], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return (new TaskCollection([$task]))
            ->additional([
                'meta' => [
                    'task_assigned_to_users_count' => $task_assigned_to_users_count,
                ]
            ]);
    } // public function get_task_by_slug($task_slug) // show 1 task by slug

}
