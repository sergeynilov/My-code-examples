<?php
namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface CrudInterface
{
    /**
     * @param string $page - paginated page, if empty - all data would be returned
     *
     * @param string $sortedBy - how data are sorted, can be combination of fields
     *
     * @param string $filters - how data are filtered
     *
     * returns a filtered data / total rows number / pagination per page
     *
     * @return array
     */
    public function filter(int $page = 1, string $sortedBy = '', array $filters = []): array;

    /**
     * Get an individual model by id
     *
     * @param string $id
     *
     * @return Model
     */
    public function get(string $id): Model;

    /**
     * Store new validated model in storage.
     *
     * @return \Illuminate\Routing\Redirector
     */
    public function store(array $data): Model;

    /**
     * Update validated model with given array in storage
     *
     * @param string $categoryId
     *
     * @param  array $data
     *
     * @return bool - if update was succesfull
     */
    public function update(Model $model, array $data): bool;

    public function delete(Model $model): void;
}
