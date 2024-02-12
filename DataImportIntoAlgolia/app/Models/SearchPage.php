<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;

class SearchPage extends Model
{
    use Searchable;
    use HasFactory;
    protected $table = 'search_pages';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $dates = ['page_created_at'];


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable
        = [
            'page_title',
            'page_slug',
            'page_content',
            'page_content_shortly',
            'page_author_name',
            'page_author_email',
            'page_price',
            'page_categories',
            'page_created_at'
        ];

    protected $casts  = [
        'page_categories' => 'array',
    ];

    public function scopeGetById($query, $id)
    {
        return $query->where(with(new SearchPage)->getTable() . '.id', $id);
    }

    public function scopeGetByTitle($query, $title = null, $partial = false)
    {
        if (empty($title)) {
            return $query;
        }

        return $query->where(with(new SearchPage)->getTable() . '.title', (! $partial ? '=' : 'like'),
            ($partial ? '%' : '') . $title . ($partial ? '%' : ''));
    }


    public function scopeGetBySlug($query, $slug = null)
    {
        if (empty($slug)) {
            return $query;
        }

        return $query->where(with(new SearchPage)->getTable() . '.slug', $slug);
    }

    protected static function boot()
    {
        parent::boot();
    }

}
