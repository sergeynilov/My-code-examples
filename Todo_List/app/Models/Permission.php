<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as Model;

class Permission extends Model
{
    //
    protected $table      = 'spt_permissions';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'guard_name',
    ];
    public function scopeGetUserByName($query, $name= null)
    {
        if (!empty($name)) {
            $query->where(with(new User)->getTable().'.name', $name);
        }
        return $query;
    }

}
