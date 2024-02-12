<?php

namespace App\Repositories;

use App\Http\Resources\PostResource;
use App\Models\PostTranslation;
use App\Models\Post;
use App\Repositories\Interfaces\CrudInterface;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;

class PostCrudRepository implements CrudInterface
{
    /*  Post CRUD(implements CrudInterface) BLOCK START */

    /**
     * Returns (filtered) paginated collection of posts
     *
     * @param int $page - paginated page, if empty - all data would be returned
     *
     * @param string $sortedBy - how data are sorted, can be combination of fields
     *
     * @param array $filters - how data are filtered, keys : text - string
     *
     * returns a filtered data / total rows number / pagination per page
     *
     * @return array : Posts - collection of found data,
     * totalPostsCount - total number of found posts,
     * paginationPerPage - number of rows in 1 paginated page
     */
    public function filter(int $page = 1, string $sortedBy = '', array $filters = []): array
    {
        $paginationPerPage = 10;

        $filterSearch = $filters['search'] ?? '';
        $sortByField = 'id';
        $sortOrdering = 'desc';

        $totalPostsCount = PostTranslation::getBySearch(search: $filterSearch, partial: true)
            ->getByLanguageId($filters['language_id'])
            ->count();
        $posts = PostTranslation::getBySearch(search: $filterSearch, partial: true)
            ->getByLanguageId($filters['language_id'])
            ->orderBy($sortByField, $sortOrdering)
            ->with('post')
            ->with('language')
            ->paginate($paginationPerPage, array('*'), 'page', $page);

        return [
            'posts' => PostResource::collection($posts),
            'totalPostsCount' => $totalPostsCount,
            'paginationPerPage' => $paginationPerPage,
        ];
    }

    /**
     * Get an individual Post model by id
     *
     * @param int $id
     *
     * @return Post
     */
    public function get(int $id): Post
    {
        $post = Post::getById($id)
            ->firstOrFail();

        return $post;
    }

    /**
     * Store new validated Post model in storage
     *
     * @return Post
     */
    public function store(array $data): Post
    {
        $post = Post::create(['created_at' => Carbon::now(config('app.timezone'))]);
        $postTranslation = PostTranslation::create([
            'post_id' => $post->id,
            'language_id' => $data['language_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'content' => $data['content'],
        ]);
        $post->load('postTranslations.language');
        $post->load('postTags.tag');

        return $post;
    }

    /**
     * Update validated Post model with given array in storage
     *
     * @param int $id
     *
     * @param array $data
     *
     * @return Post
     */
    public function update(int $id, array $data): Post
    {
        $post = Post::findOrFail($id);
        $post->updated_at = Carbon::now(config('app.timezone'));
        $post->save();

        $postTranslation = PostTranslation::updateOrCreate([
            'post_id' => $post->id,
            'language_id' => $data['language_id'],
        ], [
            'title' => $data['title'],
            'description' => $data['description'],
            'content' => $data['content'],
        ]);

        $post->load('postTranslations.language');
        $post->load('postTags.tag');
        return $post;
    }

    /**
     * Remove the specified Post model from storage
     *
     * @param int $id
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $post = Post::findOrFail($id);
        $post->delete();
    }

    /**
     *  Restore priorly trashed specified Post model in storage.
     *
     * @param int $id
     *
     * @return Model
     */
    public function restore(int $id): Model
    {
        $post = Post::withTrashed()->findOrFail($id);

        $post->restore();
        DB::commit();
        return $post;
    }

    /*  Post CRUD(implements CrudInterface) BLOCK END */
}
