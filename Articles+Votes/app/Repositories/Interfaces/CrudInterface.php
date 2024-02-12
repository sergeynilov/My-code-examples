<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;
use Illuminate\Http\Response;

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
     * @param int $id
     *
     * @return Model
     */
    public function get(int $id): JsonResponse | MessageBag;

    /**
     * Store new validated model in storage.
     *
     * @return \Illuminate\Routing\Redirector | \Illuminate\Support\MessageBag
     */
    public function store(array $data, bool $makeValidation = false): JsonResponse  | MessageBag;

    /**
     * Update validated model with given array in storage
     *
     * @param string $categoryId
     *
     * @param  array $data
     *
     * @return JsonResponse - if update was succesfull, MessageBag in case of exception
     */
    public function update(int $id, array $data, bool $makeValidation = false): JsonResponse | MessageBag;

    public function delete(int $id): Response | JsonResponse | MessageBag;
}
