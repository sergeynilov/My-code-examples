<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Repositories\Interfaces\DBTransactionInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use App\Repositories\PostCrudRepository;

class PostController extends Controller
{
    protected $postCrudRepository;
    protected $dbTransaction;

    public function __construct(PostCrudRepository $postCrudRepository, DBTransactionInterface $dbTransaction)
    {
        $this->postCrudRepository = $postCrudRepository;
        $this->dbTransaction = $dbTransaction;
    }

    /**
     * Returns (filtered) paginated collection of posts
     *
     * param in POST request : int $page - paginated page, if empty - all data would be returned
     *
     * param in POST request : string $sortedBy - how data are sorted, can be combination of fields
     *
     * param in POST request :  array $filters - how data are filtered, keys : name - string
     *
     * returns filtered data / total rows number / pagination per page
     *
     * @return array : posts - collection of found data,
     * totalPostsCount - total number of found posts,
     * paginationPerPage - number of rows in 1 paginated page
     */
    public function filter()
    {
        $request = request();
        return $this->postCrudRepository->filter(
            page: $request->page ?? 1,
            filters: $request->only('page', 'search', 'language_id'),
            sortedBy: $request->sorted_by ?? '',
        );
    }

    /**
     * Get an individual Post model by id
     *
     * @param int $id
     *
     */
    public function show(int $id)
    {
        return $this->postCrudRepository->get(id: $id);
    }

    /**
     * Validate and on success to store new post in storage.
     *
     * @return \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag
     */
    public function store(CreatePostRequest $request)
    {
        try {
            $this->dbTransaction->begin();
            $post = $this->postCrudRepository->store(data: $request->only('name', 'language_id', 'title', 'description', 'content'));
            $this->dbTransaction->commit();

            return response()->json(['post' => (new PostResource($post))], JsonResponse::HTTP_CREATED); // 201
        } catch (\Error | \Exception $e) {
            \Log::info($e->getMessage());
            $this->dbTransaction->rollback();

            return response()->json([
                'error_message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR); // 500
        }
    }

    /**
     * Update the specified Post model in storage.
     *
     * @param int $postId
     */
    public function update(UpdatePostRequest $request, int $postId)
    {
        $request = request();

        try {
            $this->dbTransaction->begin();
            $post = $this->postCrudRepository->update(id: $postId, data: $request->only('name', 'language_id', 'title', 'description', 'content'));
            $this->dbTransaction->commit();

            return response()->json(['post' => (new PostResource($post))], JsonResponse::HTTP_RESET_CONTENT); // 205
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
     * Remove the specified Post model from storage.
     *
     * @param int $postId
     *
     * @return \Illuminate\Http\Response | \Illuminate\Http\JsonResponse
     */
    public function destroy(int $postId)
    {
        try {
            $this->dbTransaction->begin();
            $this->postCrudRepository->delete(id: $postId);
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
     * Restore priorly trashed specified Post model in storage.
     *
     * @param int $postId
     *
     * @return \Illuminate\Http\Response | \Illuminate\Http\JsonResponse
     */
    public function restore(int $postId)
    {
        try {
            $this->dbTransaction->begin();
            $post = $this->postCrudRepository->restore(id: $postId);
            $this->dbTransaction->commit();
            return response()->json(['post' => (new PostResource($post))], JsonResponse::HTTP_RESET_CONTENT); // 205
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
