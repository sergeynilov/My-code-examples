<?php

namespace App\Repositories;

use App\Enums\SortByColumns;
use App\Enums\TaskStatus;
use App\Exceptions\ServerSpError;
use App\Exceptions\TaskAccessByOtherUserRestrictedException;
use App\Library\Services\ServerRequestWithPDO;
use App\Models\Task;
use App\Repositories\Interfaces\CrudRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\DTO\TasksFilterDTO;
use App\DTO\TaskDTO;
use Illuminate\Support\Facades\DB;
use PDO;
use WendellAdriel\ValidatedDTO\SimpleDTO;

class TaskCrudMysqlRepository implements CrudRepositoryInterface
{
    /**
     * Search tasks data by provided criteria
     *
     * @param int $page - paginated page, if empty - all data would be returned
     *
     * @param SimpleDTO $filtersDTO (string search, string status, string priority, string sortBy)
     * possible filters
     *
     * returns filtered data
     *
     * @return Collection of Models - collection of found data,
     */
    public function filter(?int $page, ?SimpleDTO $filtersDTO = null): Collection
    {
        $paginationPerPage = (int)config('app.pagination_per_page', 20);
        $limitStart = ($page - 1) * $paginationPerPage;
        $orderBys = [['field' => $filtersDTO->sortBy ?? 'id', 'ordering' => $filtersDTO->orderBy ?? 'asc']];

        if (SortByColumns::tryFrom($filtersDTO->sortBy) === SortByColumns::CREATED_AT) {
            $orderBys = [
                ['field' => 'created_at', 'ordering' => 'asc'],
                ['field' => 'id', 'ordering' => 'desc'],
            ];
        }
        if (SortByColumns::tryFrom($filtersDTO->sortBy) === SortByColumns::PRIORITY_CREATED_AT) {
            $orderBys = [
                ['field' => 'priority', 'ordering' => 'desc'],
                ['field' => 'created_at', 'ordering' => 'asc'],
            ];
        }
        if (SortByColumns::tryFrom($filtersDTO->sortBy) === SortByColumns::STATUS_PRIORITY_CREATED_AT) {
            $orderBys = [
                ['field' => 'status', 'ordering' => 'desc'],
                ['field' => 'priority', 'ordering' => 'desc'],
                ['field' => 'created_at', 'ordering' => 'asc'],
            ];
        }
        $tasks = Task::getBySearch(search: $filtersDTO->search, partial: true)
            ->getBycreatorId((int)$filtersDTO->creatorId)
            ->getByStatus($filtersDTO->status)
            ->getByPriority($filtersDTO->priority)
            ->offset($limitStart)
            ->take($paginationPerPage)
            ->with('parentTask')
            ->orderByArray($orderBys)
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
            ->with('creator')
            ->with('parentTask')
            ->with('category')
            ->with('taskUsers')
            ->with('taskUsers.user')
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
     * @param SimpleDTO $data
     *
     * @return Task
     */
    public function store(SimpleDTO $data): Task
    {
        $taskId = (new ServerRequestWithPDO())->call("call sp_taskInsert(?, ?, ?, ?, ?, ?, ?, ?, ?, @out_returnCode);",
            [
                /* IN in_parentId bigint unsigned */ $data->parent_id ?? null,
                /* IN in_creatorId bigint unsigned, */ $data->creator_id ?? auth()->user()->id,
                /* IN in_title varchar(255) */ $data->title,
                /* IN in_categoryId smallint unsigned */ $data->category_id,
                /* IN in_priority varchar(1) */ $data->priority,
                /* IN in_weight int unsigned */ $data->weight,
                /* IN in_status varchar(255) */ $data->status,
                /* IN in_description mediumtext */ $data->description,
                /* IN in_completed_at datetime */ $data->completed_at]);

        $task = Task::getById($taskId ?? '')
            ->with('creator')
            ->with('parentTask')
            ->with('category')
            ->with('taskUsers')
            ->with('taskUsers.user')
            ->firstOrFail();

        return $task;
    }

    /**
     * Update validated Task model with given array in storage
     *
     * @param int $id
     *
     * @param SimpleDTO $data
     *
     * @return Task
     */
    public function update(int $id, SimpleDTO $data): Task
    {
        $task = Task::findOrFail($id);

        $taskId = (new ServerRequestWithPDO())->call("call sp_taskUpdate(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @out_returnCode);",
            [
                /* IN in_taskId bigint unsigned */ $id,
                /* IN in_parentId bigint unsigned */ $data->parent_id ?? null,
                /* IN in_title varchar(255) */ $data->title,
                /* IN in_categoryId smallint unsigned */ $data->category_id,
                /* IN in_priority varchar(1) */ $data->priority,
                /* IN in_weight int unsigned */ $data->weight,
                /* IN in_status varchar(255) */ $data->status,
                /* IN in_description mediumtext */ $data->description,
                /* IN in_completed_at datetime */ $data->completed_at,
                /* IN in_loggedUserId bigint unsigned */ auth()->user()->getAttribute('id')]);

        $task = $task->fresh();
        $task->load('creator');
        $task->load('category');
        $task->load('parentTask');
        $task->load('taskUsers');

        return $task;
    }

    /**
     * Set status DONE to the specified Task model
     *
     * @param int $id
     *
     * @param bool $completeSubtasks - if true, all subtasks would be completed. If false custom error would be triggered
     *
     * @return Task
     */
    public function done(int $id, bool $completeSubtasks = false): Task
    {
        $task = Task::findOrFail($id);
        $taskId = (new ServerRequestWithPDO())->call("call sp_taskComplete(?, ?, ?, @out_returnCode);",
            [
                /*IN in_taskId bigint unsigned*/ $id,
                /*IN in_completeSubtasks tinyint unsigned*/ ($completeSubtasks ? 1 : 0),
                /*IN in_userId bigint unsigned*/ Auth::user()->id
            ]);

        $task->load('creator');
        $task->load('category');
        $task->load('parentTask');
        $task->load('taskUsers');

        return $task;
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
        (new ServerRequestWithPDO())->call("call sp_taskDelete(?, ?, @out_returnCode);",
            [
                /*IN in_taskId bigint unsigned*/ $id,
                /*IN in_loggedUserId bigint unsigned*/ Auth::user()->id
            ]);
    }
}
