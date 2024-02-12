<?php

namespace App\Library;

use Illuminate\Support\Facades\Schema;

class StubCodeContainer
{
    /**
     * Get crud controller help text
     *
     * @return array
     */
    public function getCRUDControllerHelp(): array
    {
        return [
            'Create a new CRUD Controller based on stubs/custom.controllers.stub file and using provided model name',
            'In capitalized singular capitalize name, like User - required',
            'Param showDone -  if set then done/undone methods are added - optional',
            'Param transactionWithInterface -  if set then transaction with interface class are used - optional',
            'Param help -  if set then help text is shown, but new controller is not created - optional',
            'example : php artisan make:custom-controller ProductCategory showDone  transactionWithInterface',
            'OR this help : php artisan make:custom-controller help',
            'Examples :',
            'php artisan make:custom-controller ProductCategory showDone transactionWithInterface',
            'OR',
            'php artisan make:custom-controller Product',
            'OR',
            'TEST Help:',
            'php artisan make:custom-controller help',
        ];
    }

    /**
     * Get model help text
     *
     * @return array
     */
    public function getModelHelp(): array
    {
        return [
            'Create a new Model based on stubs/custom.model.stub file and using provided model name',
            'In capitalized singular capitalize name, like User - required',
            'Param userRelation - if set User scope and BelongsTo methods are added',
            'Param productRelation - if set Product scope and BelongsTo methods are added',
            'Param productCityRelation - if set City scope and HasMany methods are added',
            'Param categoryRelation - if set Category scope and BelongsTo methods are added',
            'Param discountsRelation - if set Discount BelongsToMany methods is added',

            'OR this help : php artisan make:custom-model help',

            'Examples :',
            'php artisan make:custom-model ProductCategory userRelation productRelation categoryRelation',
            'OR',
            'php artisan make:custom-model Product creatorRelation productCityRelation discountsRelation',

            'TEST Help:',
            'php artisan make:custom-controller help',
        ];
    }

    /**
     * Return method code to be used in model by cviebrock/eloquent-sluggable plugin
     *
     * @param string $slugSourceFieldName
     *
     * @return string
     */
    public function getSluggableFieldCode(string $slugSourceFieldName): string
    {
        return "    public function sluggable() : array
    {
        return [
            'slug' => [
                'source' => '" . $slugSourceFieldName . "'
            ]
        ];
    }";
    }

    /**
     * Return scope method code used in model to get ID of the model
     *
     *
     * @return string
     */
    public function getGetByIdCode(): string
    {
        return '
        public function scopeGetById($query, $id)
    {
        if (empty($id)) {
            return $query;
        }
        return $query->where($this->table . \'.id\', $id);
    }
';
    }

    /**
     * Return user relation methods code used in model, which is related with User model
     *
     * @return string
     */
    public function getUserRelationCode(): string
    {
        return '
    public function scopeGetByUserId($query, int $userId = null)
    {
        if ( ! empty($userId)) {
            $query->where($this->table . \'.user_id\', $userId);
        }

        return $query;
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

';
    }

    /**
     * Return creator relation methods code used in model, which is related with Creator/User model
     *
     * @return string
     */
    public function getCreatorRelationCode(): string
    {
        return '
    public function scopeGetByCreatorId($query, $creatorId = null)
    {
        if (empty($creatorId)) {
            return $query;
        }

        return $query->where(with(new Product)->getTable() . \'.creator_id\', $creatorId);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, \'.creator_id\', \'id\');
    }

';
    }

    /**
     * Return product relation methods code used in model, which is related with Product model
     *
     * @param string $sourceModel - source model related to Product
     *
     * @return string
     */
    public function getProductRelationCode(string $sourceModel): string
    {
        return '

    public function scopeGetByProductId($query, int $product_id= null)
    {
        if (!empty($product_id)) {
            $query->where(with(new ' . $sourceModel . ')->getTable() . \'.product_id\', $product_id);
        }
        return $query;
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, \'.product_id\', \'id\');
    }

';
    }

    /**
     * Return product city relation methods code used in model, which is related with ProductCity model
     *
     * @return string
     */
    public function getProductCityRelationCode(): string
    {
        return '

    public function productCities(): HasMany
    {
        return $this->hasMany(ProductCity::class, \'product_id\', \'id\');
    }

    public function scopeGetByCityId($query, $cityId = null)
    {
        if (empty($cityId)) {
            return $query;
        }

        return $query->where(with(new Product)->getTable() . \'.city_id\', $cityId);
    }

';
    }

    /**
     * Return category relation methods code used in model, which is related with Category model
     *
     * @param string $sourceModel - source model related to Category
     *
     *  @return string
     */
    public function getCategoryRelationCode(string $sourceModel): string
    {
        return '

    public function scopeGetByCategoryId($query, $category_id = null)
    {
        if (empty($category_id)) {
            return $query;
        }

        return $query->where(with(new ' . $sourceModel . ')->getTable() . \'.category_id\', $category_id);
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

';
    }

    /**
     * Return discount relation methods code used in model, which is related with Discount model
     *
     * @return string
     */
    public function getDiscountsRelationCode(): string
    {
        return '

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, \'discount_product\');
    }

';
    }

    /**
     * Return product category relation methods code used in model, which is related with ProductCategory model
     *
     * @return string
     */
    public function getProductCategoriesRelationCode(): string
    {
        return '

    public function productCategories(): HasMany
    {
        return $this->hasMany(ProductCategory::class, \'.product_id\', \'id\');
    }

    public function categories(): HasManyThrough
    {
        return $this->hasManyThrough(ProductCategory::class, Category::class);
    }

    public function scopeOnlyActiveCategories() : HasManyThrough
    {
        return $this->hasManyThrough(ProductCategory::class, Category::class)
                    ->where(with(new Category)->getTable() . \'.active\', true);
    }

';
    }

    /**
     * Return Fillable code used in model, based on model' table
     *
     * @param string $tableName model' table
     *
     * @return string
     */
    public function getFillableFromDbCode(string $tableName): string
    {
        $columns = Schema::getColumnListing( $tableName);
        $retCode = '';
        foreach( $columns as $column ) {
            if(!in_array($column, ['id', 'updated_at', 'created_at'])) {
                $retCode .= "'" . $column . "'" . ', ';
            }
        }
        return trimRightSubString($retCode, ', ');
    }

}
