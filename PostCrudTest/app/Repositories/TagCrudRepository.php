<?php

namespace App\Repositories;

use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Repositories\Interfaces\CrudInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TagCrudRepository implements CrudInterface
{
    /*  Tag CRUD(implements CrudInterface) BLOCK START */

    /**
     * Returns (filtered) paginated collection of tags
     *
     * @param int $page - paginated page, if empty - all data would be returned
     *
     * @param string $sortedBy - how data are sorted, can be combination of fields
     *
     * @param array $filters - how data are filtered, keys : name - string
     *
     * returns a filtered data / total rows number / pagination per page
     *
     * @return array : Tags - collection of found data,
     * totalTagsCount - total number of found tags,
     * paginationPerPage - number of rows in 1 paginated page
     */
    public function filter(int $page = 1, string $sortedBy = '', array $filters = []): array
    {
        $paginationPerPage = 10;

        $filterSearch = $filters['search'] ?? '';
        $sortByField = 'id';
        $sortOrdering = 'desc';

        $totalTagsCount = Tag::getBySearch(search: $filterSearch, partial: true)
            ->count();
        $tags = Tag::getBySearch(search: $filterSearch, partial: true)
            ->orderBy($sortByField, $sortOrdering)
            ->with('postTags')
            ->paginate($paginationPerPage, array('*'), 'page', $page);

        return [
            'tags' => TagResource::collection($tags),
            'totalTagsCount' => $totalTagsCount,
            'paginationPerPage' => $paginationPerPage,
        ];
    }

    /**
     * Get an individual Tag model by id
     *
     * @param int $id
     *
     * @return Tag
     */
    public function get(int $id): Tag
    {
        $tag = Tag::getById($id)
            ->firstOrFail();

        return $tag;
    }

    /**
     * Store new validated Tag model in storage
     *
     * @return Tag
     */
    public function store(array $data): Tag
    {
        $tag = Tag::create([
            'name' => $data['name'],
        ]);

        return $tag;
    }

    /**
     * Update validated Tag model with given array in storage
     *
     * @param int $id
     *
     * @param array $data
     *
     * @return Model
     */
    public function update(int $id, array $data): Model
    {
        $tag = Tag::findOrFail($id);
        $data['updated_at'] = Carbon::now(config('app.timezone'));
        $tag->update($data);

        return $tag; // 205
    }

    /**
     * Remove the specified Tag model from storage
     *
     * @param int $id
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();
    }

    /**
     *  Restore priorly trashed specified Tag model in storage.
     *
     * @param int $id
     *
     * @return Model
     */
    public function restore(int $id): Model
    {
        $tag = Tag::withTrashed()->findOrFail($id);
        $tag->restore();

        return $tag;
    }

    /*  Tag CRUD(implements CrudInterface) BLOCK END */
}
