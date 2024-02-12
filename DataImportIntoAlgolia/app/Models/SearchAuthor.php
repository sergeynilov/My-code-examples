<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SearchAuthor extends Model implements HasMedia
{
    use Searchable;
    protected $table = 'search_authors';
    protected $primaryKey = 'id';
    public $timestamps = false;
    use HasFactory;
    use InteractsWithMedia;

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable
        = [
            'author_name',
            'author_email',
            'author_first_name',
            'author_last_name',
            'author_phone',
            'author_website',
            'author_created_at',
            'pages_count',
        ];

    public function scopeGetById($query, $id)
    {
        return $query->where(with(new SearchAuthor)->getTable() . '.id', $id);
    }

    public function scopeGetByName($query, $name = null)
    {
        if (empty($name)) {
            return $query;
        }

        return $query->where(with(new SearchAuthor)->getTable() . '.name', 'like', '%' . $name . '%');
    }

    public function scopeGetByEmail($query, $email = null)
    {
        if (empty($email)) {
            return $query;
        }

        return $query->where(with(new SearchAuthor)->getTable() . '.email', 'like', '%' . $email . '%');
    }

}
