<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use DB;
use Carbon\Carbon;
use Config;

use Illuminate\Support\Facades\File;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SearchNews extends Model implements HasMedia
{
    use Searchable;
    use InteractsWithMedia;
    use Notifiable;
    use Sluggable;

    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = 'search_news';

    protected $fillable
        = [
            'id',
            'news_title',
            'news_slug',
            'news_content',
            'news_content_shortly',
            'creator_author_name',
            'creator_author_email',
            'news_is_homepage',
            'news_is_top',
            'news_created_at',
        ];


    protected $casts
        = [
        ];


    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($searchNews) {
        });
    }

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }


}
