<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;



/**
 * App\Models\ModelHasPermission
 *
 * @property int $permission_id
 * @property string $model_type
 * @property int $model_id
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasPermission getByModelId($model_id)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasPermission getByPermissionId($permission_id)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasPermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasPermission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasPermission query()
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasPermission whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasPermission whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ModelHasPermission wherePermissionId($value)
 * @mixin \Eloquent
 */
class ModelHasPermission extends Model
{
    protected $table      = 'spt_model_has_permissions';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    public function scopeGetByPermissionId($query, $permission_id)
    {
        return $query->where(with(new ModelHasPermission)->getTable() . '.permission_id', $permission_id);
    }


    public function scopeGetByModelId($query, $model_id)
    {
        return $query->where(with(new ModelHasPermission)->getTable() . '.model_id', $model_id);
    }


}

