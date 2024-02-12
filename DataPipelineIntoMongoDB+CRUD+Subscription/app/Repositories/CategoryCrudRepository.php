<?php
namespace App\Repositories;

use App\Enums\CheckValueType;
use App\Models\Category;
use App\Models\ProductCategory;
use App\Models\Settings;
use App\Repositories\Interfaces\CrudInterface;
use DateConv;
use Illuminate\Database\Eloquent\Model;

class CategoryCrudRepository implements CrudInterface
{
    /**
     * Returns (filtered) paginated collection of Categories
     *
     * @param string $page - paginated page, if empty - all data would be returned
     *
     * @param string $sortedBy - how data are sorted, can be combination of fields
     *
     * @param string $filters - how data are filtered, keys : active(0/1), name - string, import_notice - string
     *
     * returns filtered data / total rows number / pagination per page
     *
     * @return array : categories - collection of found data,
     * totalCategoriesCount - total number of found Categories,
     * paginationPerPage - number of rows in 1 paginated page
     */
    public function filter(int $page = 1, string $sortedBy = '', array $filters = []): array
    {
        $paginationPerPage = Settings::getValue('pagination_per_page', CheckValueType::cvtInteger, 20);
        $filterActive       = $filters['active'] ?? '';
        $filterName         = $filters['name'] ?? '';
        $filterImportNotice = $filters['import_notice'] ?? null;
        $sortByField            = 'active';
        $sortOrdering           = 'desc';
        $additive_sort_by_field = 'name';
        $additive_sort_ordering = 'asc';
        if ($sortedBy == 'active_name') {
            $sortByField            = 'active';
            $sortOrdering           = 'desc';
            $additive_sort_by_field = 'name';
            $additive_sort_ordering = 'asc';
        }
        if ($sortedBy == 'related_users_count') {
            $sortByField            = 'user_categories_count';
            $sortOrdering           = 'asc';
            $additive_sort_by_field = 'name';
            $additive_sort_ordering = 'asc';
        }

        $totalCategoriesCount = Category
            ::getByName($filterName)
            ->getByActive($filterActive)
            ->onlyWithImportNoticesNoneEmpty($filterImportNotice)
            ->count();
        $categories = Category
            ::getByName($filterName)
            ->getByActive($filterActive)
            ->onlyWithImportNoticesNoneEmpty($filterImportNotice)
            ->orderBy($sortByField, $sortOrdering)
            ->orderBy($additive_sort_by_field, $additive_sort_ordering)
            ->paginate($paginationPerPage, array('*'), 'page', $page)
            ->through(function ($categoryItem) {
                $categoryItem->created_at_formatted     = DateConv::getFormattedDateTime($categoryItem->created_at);
                $categoryItem->active_label             = Category::getActiveLabel($categoryItem->active);
                $categoryItem->product_categories_count = ProductCategory
                    ::getByCategoryId($categoryItem->_id)
                    ->count();

                return $categoryItem;
            });

        return [
            'categories'           => $categories,
            'totalCategoriesCount' => $totalCategoriesCount,
            'paginationPerPage'    => $paginationPerPage,
        ];
    }

    /**
     * Get an individual Category model by id
     *
     * @param string $id
     *
     * @return Model
     */
    public function get(string $id): Model
    {
        return Category
            ::getById($id)
            ->firstOrFail();
    }

    /**
     * Store new validated Category model in storage
     *
     * @return Model
     */
    public function store(array $data): Model
    {
        $data['active'] = ! empty($data['active']);

        return Category::create([
            'name'        => $data['name'],
            'active'      => $data['active'],
            'description' => $data['description'],
        ]);
    }

    /**
     * Update validated Category model with given array in storage
     *
     * @param string $categoryId
     *
     * @param  array $data
     *
     * @return bool - if update was succesfull
     */
    public function update(Model $model, array $data): bool
    {
        $data['active'] = ! empty($data['active']);
        return $model->update($data);
    }

    /**
     * Remove the specified Category model from storage
     *
     * @param Category Model $model
     *
     * @return void
     */
    public function delete(Model $model): void
    {
        $model->delete();
    }
}

?>
