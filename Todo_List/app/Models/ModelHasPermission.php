<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;



class ModelHasPermission extends Model
{
    protected $table      = 'spt_model_has_permissions';
    protected $primaryKey = 'id';
    public $timestamps    = false;


    public function user()
    {
        return $this->belongsTo('App\Models\User', 'model_id');
    }

    public function scopeGetByPermissionId($query, $permission_id)
    {
        return $query->where(with(new ModelHasPermission)->getTable() . '.permission_id', $permission_id);
    }



    public function scopeGetByModelId($query, $model_id)
    {
        return $query->where(with(new ModelHasPermission)->getTable() . '.model_id', $model_id);
    }


}

