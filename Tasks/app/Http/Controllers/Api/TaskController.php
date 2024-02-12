<?php

namespace App\Http\Controllers\Api;

use App\DTO\TaskDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Repositories\Interfaces\CrudRepositoryInterface;
use App\Repositories\Interfaces\DBTransactionInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    protected CrudRepositoryInterface $taskCrudRepository;
    protected DBTransactionInterface $dbTransaction;

    public function __construct(CrudRepositoryInterface $taskCrudRepository, DBTransactionInterface $dbTransaction)
    {
        $this->taskCrudRepository = $taskCrudRepository;
        $this->dbTransaction = $dbTransaction;
    }

    /**
     * Search data and returns (filtered) paginated collection of tasks
     *
     * param in POST request : int $page - paginated page, if empty - all data would be returned
     *
     * param in POST request : string $sortedBy - how data are sorted, can be combination of fields
     *
     * param in POST request :  array $filters - how data are filtered, keys : name - string
     *
     * returns filtered data / total rows number / pagination per page
     *
     * @return AnonymousResourceCollection of Models - collection of found data,
     */
    public function filter()
    {
        $request = request();

        $data = $this->taskCrudRepository->filter(
            page: $request->page ?? 1,
            filters: $request->only('search', 'status', 'userId', 'priority', 'sortBy', 'orderBy'),
        );
        return TaskResource::collection($data);
    }

    /**
     * Get an individual Task model by id
     *
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     */
    public function show(int $id): \Illuminate\Database\Eloquent\Model
    {
        return $this->taskCrudRepository->get(id: $id);
    }

    /**
     * Validate and on success to store new task in storage.
     *
     * @return \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag
     */
    public function store(CreateTaskRequest $request)
    {

        try {
            $this->dbTransaction->begin();
            $task = $this->taskCrudRepository->store(dtoData: TaskDTO::fromRequest($request));
            $this->dbTransaction->commit();

            return response()->json(['task' => (new TaskResource($task))], JsonResponse::HTTP_CREATED); // 201
        } catch (\Error | \Exception $e) {
            \Log::info($e->getMessage());
            $this->dbTransaction->rollback();

            return response()->json([
                'error_message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR); // 500
        }
    }

    /**
     * Update the specified Task model in storage.
     *
     * @param int $taskId
     *
     * @return JsonResponse
     */
    public function update(UpdateTaskRequest $request, int $taskId): JsonResponse
    {
        $request = request();
        try {
            $this->dbTransaction->begin();
            $task = $this->taskCrudRepository->update(id: $taskId, dtoData: TaskDTO::fromRequest($request));
            $this->dbTransaction->commit();

            return response()->json(['task' => (new TaskResource($task))], JsonResponse::HTTP_RESET_CONTENT); // 205
        } catch (ModelNotFoundException $e) {
            \Log::info($e->getMessage());
            $this->dbTransaction->rollback();
            throw new ModelNotFoundException($e->getMessage(), JsonResponse::HTTP_NOT_FOUND);
        } catch (\Error | \Exception $e) {
            \Log::info($e->getMessage());
            $this->dbTransaction->rollback();

            return response()->json([
                'error_message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR); // 500
        }
    }

    /**
     * Set status DONE to the specified Task model
     *
     * @param int $taskId
     *
     * @return JsonResponse
     */
    public function done(int $taskId): JsonResponse
    {
        try {
            $this->dbTransaction->begin();
            $task = $this->taskCrudRepository->done(id: $taskId);
            $this->dbTransaction->commit();

            return response()->json(['task' => (new TaskResource($task))], JsonResponse::HTTP_RESET_CONTENT); // 205
        } catch (ModelNotFoundException $e) {
            \Log::info($e->getMessage());
            $this->dbTransaction->rollback();
            throw new ModelNotFoundException($e->getMessage(), JsonResponse::HTTP_NOT_FOUND);
        } catch (\Error | \Exception $e) {
            \Log::info($e->getMessage());
            $this->dbTransaction->rollback();
            return response()->json([
                'error_message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR); // 500
        }
    }

    /**
     * Remove the specified Task model from storage.
     *
     * @param int $taskId
     *
     * @return \Illuminate\Http\Response | \Illuminate\Http\JsonResponse
     */
    public function destroy(int $taskId): \Illuminate\Http\Response | \Illuminate\Http\JsonResponse
    {
        try {
            $this->dbTransaction->begin();
            $this->taskCrudRepository->delete(id: $taskId);
            $this->dbTransaction->commit();
        } catch (ModelNotFoundException $e) {
            \Log::info($e->getMessage());
            $this->dbTransaction->rollback();
            throw new ModelNotFoundException($e->getMessage(), JsonResponse::HTTP_NOT_FOUND);
        } catch (\Error | \Exception $e) {
            \Log::info($e->getMessage());
            $this->dbTransaction->rollback();

            return response()->json([
                'error_message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR); // 500
        }
        return response()->noContent();
    }
}
