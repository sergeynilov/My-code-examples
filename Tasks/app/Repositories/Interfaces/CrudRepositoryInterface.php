<?php

namespace App\Repositories\Interfaces;

use App\DTO\TaskDTO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface CrudRepositoryInterface
{
    /**
     * Search data and returns (filtered) paginated collection of models
     *
     * @param int $page - paginated page, if empty - all data would be returned
     *
     * @param array $filters - how data are filtered, keys : text - string
     *
     * returns filtered data
     *
     * @return Collection of Models - collection of found data,
     */
    public function filter(int $page = 1, array $filters = []): Collection;

    /**
     * Get an individual Model model by id
     *
     * @param int $id
     *
     * @return Model
     */
    public function get(int $id): Model;

    /**
     * Store new validated Model model in storage
     *
     * @param TaskDTO $dtoData
     *
     * @return Model
     */
    public function store(TaskDTO $dtoData): Model;

    /**
     * Update validated Model model with given array in storage
     *
     * @param int $id
     *
     *
     * @param TaskDTO $dtoData
     *
     * @return Model
     */
    public function update(int $id, TaskDTO $dtoData): Model;

    /**
     * Set status DONE to the specified Model model
     *
     * @param int $id
     *
     * @return Model
     */
    public function done(int $id): Model;

    /**
     * Remove the specified Model model from storage
     *
     * @param int $id
     *
     * @return void
     */
    public function delete(int $id);
}
