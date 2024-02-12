<?php

namespace App\Repositories;

use App\Enums\TaskStatus;
use App\Exceptions\TaskAccessByOtherUserRestricted;
use App\Models\Task;
use App\Repositories\Interfaces\CrudRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\DTO\TaskDTO;

class TaskCrudRepository implements CrudRepositoryInterface
{
    /**
     * Search tasks data by provided criteria
     *
     * @param int $page - paginated page, if empty - all data would be returned
     *
     * @param array<string> $filters (string search, string status, string priority, string sortBy, string ordering)
     * possible filters
     *
     * returns filtered data
     *
     * @return Collection of Models - collection of found data,
     */
    public function filter(int $page = 1, array $filters = []): Collection
    {
        $paginationPerPage = (int)config('app.pagination_per_page', 20);
        $limitStart = ($page - 1) * $paginationPerPage;
        $filterSearch = $filters['search'] ?? '';
        $userId = $filters['userId'] ?? '';
        $status = $filters['status'] ?? '';
        $priority = $filters['priority'] ?? '';
        $sortByField = $filters['sortBy'] ?? 'id';
        $orderBy = $filters['orderBy'] ?? 'asc';
        $sortByField2 = 'id';
        $orderBy2 = 'asc';
        $sortByField3 = 'id';
        $orderBy3 = 'asc';

        if (!empty($filters['sortBy']) and $filters['sortBy'] === 'priority_created_at') {
            $sortByField = 'priority';
            $orderBy = 'desc';
            $sortByField2 = 'created_at';
            $orderBy2 = 'asc';
        }
        if (!empty($filters['sortBy']) and $filters['sortBy'] === 'status_priority_created_at') {
            $sortByField = 'status';
            $orderBy = 'desc';
            $sortByField2 = 'priority';
            $orderBy2 = 'desc';
            $sortByField3 = 'created_at';
            $orderBy3 = 'asc';
        }
        $tasks = Task::getBySearch(search: $filterSearch, partial: true)
            ->getByUserId((int)$userId)
            ->getByStatus($status)
            ->getByPriority($priority)
            ->offset($limitStart)
            ->take($paginationPerPage)
            ->with('parentTask')
            ->with('user')
            ->orderBy($sortByField, $orderBy)
            ->orderBy($sortByField2, $orderBy2)
            ->orderBy($sortByField3, $orderBy3)
            ->get();

        return $tasks;
    }

    /**
     * Get an individual Task model by id
     *
     * @param int $id
     *
     * @return Task
     */
    public function get(int $id): Task
    {
        $task = Task::getById($id)
            ->with('parentTask')
            ->with('user')
            ->firstOrFail();

        $tasks = Task::getByParentId($task->id)->pluck('id');
        $childrenTasks = [];
        foreach ($tasks as $nextTaskId) {
            $childrenTasks[] = $this->get(id: $nextTaskId);
        }
        $task->setAttribute('children', $childrenTasks);

        return $task;
    }

    /**
     * Store new validated Task model in storage
     *
     * @param TaskDTO $dtoData *
     *
     * @return Task
     */
    public function store(TaskDTO $dtoData): Task
    {
        $task = Task::create([
            'user_id' => $dtoData->user_id ?? auth()->user()->id,
            'title' => $dtoData->title,
            'description' => $dtoData->description,
            'priority' => $dtoData->priority,
            'status' => $dtoData->status,
            'completed_at' => $dtoData->completed_at,
        ]);

        return $task;
    }

    /**
     * Update validated Task model with given array in storage
     *
     * @param int $id
     *
     * @param TaskDTO $dtoData
     *
     * @return Task
     */
    public function update(int $id, TaskDTO $dtoData): Task
    {
        $task = Task::findOrFail($id);
        throw_if(
            $task->user_id !== Auth::user()->id,
            TaskAccessByOtherUserRestricted::class,
            __('You are not allowed to edit this task')
        );

        $task->update([
            'title' => $dtoData->title,
            'description' => $dtoData->description,
            'priority' => $dtoData->priority,
            'status' => $dtoData->status,
            'completed_at' => $dtoData->completed_at,
        ]);
        $task->load('parentTask');
        $task->load('user');

        return $task;
    }

    /**
     * Set status DONE to the specified Task model
     *
     * @param int $id
     *
     * @return Task
     */
    public function done(int $id): Task
    {
        $task = Task::findOrFail($id);
        throw_if(
            $task->user_id !== Auth::user()->id,
            TaskAccessByOtherUserRestricted::class,
            __('You are not allowed to complete this task')
        );
        $task->update([
            'status' => TaskStatus::DONE->value,
            'completed_at' => Carbon::now(config('app.timezone')),
        ]);

        return $task;
    }

    /**
     * check incomplete subtasks by Task model by id
     *
     * @param int $id
     *
     * @return bool if no incomplete subtasks found
     */
    public function checkIncomplete(int $id): bool
    {
        $tasks = Task::getByParentId($id)->get();
        foreach ($tasks as $task) {
            throw_if(
                $task->status === TaskStatus::TODO->value,
                TaskAccessByOtherUserRestricted::class,
                __('Can not complete this task as it has incomplete subtask(s)')
            );
            $this->checkIncomplete($task->id);
        }

        return false;
    }

    /**
     * Remove the specified Task model from storage
     *
     * @param int $id
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $task = Task::findOrFail($id);

        throw_if(
            $task->user_id !== Auth::user()->id,
            TaskAccessByOtherUserRestricted::class,
            __('You are not allowed to delete this task')
        );

        throw_if(
            $task->status === TaskStatus::DONE->value,
            TaskAccessByOtherUserRestricted::class,
            __('You can not delete completed task')
        );

        $task->delete();
    }
}
