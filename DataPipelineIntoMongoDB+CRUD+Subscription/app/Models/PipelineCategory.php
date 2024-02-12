<?php

namespace App\Models;

use Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Cviebrock\EloquentSluggable\Sluggable;

class PipelineCategory extends Model
{
    use HasFactory;
    use Sluggable;

    protected $connection = 'pipeline_source';
    protected $table = 'ts_categories';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable
        = [
            'name',
            'active',
            'description',
            'imported_date'
        ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    public function pipelineProductCategories()
    {
        return $this->hasMany('App\Models\ProductPipelineCategory', 'category_id', 'id');
    }

    public function scopeOnlyActive($query)
    {
        return $query->where(with(new PipelineCategory)->getTable() . '.active', true);
    }

    public function scopeGetByActive($query, $active = null)
    {
        if ( ! isset($active) or strlen($active) == 0) {
            return $query;
        }

        return $query->where(with(new PipelineCategory)->getTable() . '.active', $active);
    }

}
