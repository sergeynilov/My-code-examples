<?php

namespace App\Http\Controllers;

use App\Http\Resources\TagResource;
use App\Http\Requests\CreateTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Repositories\Interfaces\DBTransactionInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use App\Repositories\TagCrudRepository;

class TagController extends Controller
{
    protected $tagCrudRepository;
    protected $dbTransaction;

    public function __construct(TagCrudRepository $tagCrudRepository, DBTransactionInterface $dbTransaction)
    {
        $this->tagCrudRepository = $tagCrudRepository;
        $this->dbTransaction = $dbTransaction;
    }

    /**
     * Returns (filtered) paginated collection of tags
     *
     * param in POST request : int $page - paginated page, if empty - all data would be returned
     *
     * param in POST request : string $sortedBy - how data are sorted, can be combination of fields
     *
     * param in POST request : array $filters - how data are filtered, keys : name - string
     *
     * returns filtered data / total rows number / pagination per page
     *
     */
    public function filter()
    {
        $request = request();

        return response()->json(
            $this->tagCrudRepository->filter(
                page: $request->page ?? 1,
                filters: $request->only('page', 'search'),
                sortedBy: $request->sorted_by ?? ''
            )
        );
    }

    /**
     * Get an individual Tag model by id
     *
     * @param int $id
     */
    public function show(int $id)
    {
        return $this->tagCrudRepository->get(id: $id);
    }

    /**
     * Validate and on success to store new tag in storage.
     */
    public function store(CreateTagRequest $request)
    {
        try {
            $this->dbTransaction->begin();
            $tag = $this->tagCrudRepository->store(data: $request->only('name'));
            $this->dbTransaction->commit();

            return response()->json(['tag' => (new TagResource($tag))], JsonResponse::HTTP_CREATED); // 201
        } catch (\Error | \Exception $e) {
            \Log::info($e->getMessage());
            $this->dbTransaction->rollback();

            return response()->json([
                'error_message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR); // 500
        }
    }

    /**
     * Update the specified Tag model in storage.
     *
     * @param int $tagId
     */
    public function update(UpdateTagRequest $request, int $tagId)
    {
        try {
            $this->dbTransaction->begin();
            $tag = $this->tagCrudRepository->update(id: $tagId, data: $request->only('name'));
            $this->dbTransaction->commit();

            return response()->json(['tag' => (new TagResource($tag))], JsonResponse::HTTP_RESET_CONTENT); // 205
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
     * Remove the specified Tag model from storage.
     *
     * @param int $tagId
     */
    public function destroy(int $tagId)
    {
        try {
            $this->dbTransaction->begin();
            $this->tagCrudRepository->delete(id: $tagId);
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

    /**
     * Restore priorly trashed specified Tag model in storage.
     *
     * @param int $tagId
     */
    public function restore(int $tagId)
    {
        try {
            $this->dbTransaction->begin();
            $tag = $this->tagCrudRepository->restore(id: $tagId);
            $this->dbTransaction->commit();

            return response()->json(['tag' => (new TagResource($tag))], JsonResponse::HTTP_RESET_CONTENT); // 205
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
}
