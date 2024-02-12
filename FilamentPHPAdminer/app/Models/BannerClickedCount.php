<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BannerClickedCount extends Model
{
    use HasFactory;

    protected $table = 'banner_clicked_counts';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['banner_id', 'locale', 'user_id', 'ip'];

    protected $guarded = ['created_at'];

    public function scopeGetById($query, $id)
    {
        $query->where($this->table . '.id', $id);
    }

    public function scopeGetByBannerId($query, $bannerId = null)
    {
        if (empty($bannerId)) {
            return $query;
        }

        return $query->where((new BannerClickedCount)->getTable() . '.banner_id', $bannerId);
    }

    public function scopeGetByPageContentId($query, $bannerId = null)
    {
        if (empty($bannerId)) {
            return $query;
        }

        return $query->where((new BannerClickedCount)->getTable() . '.page_content_id', $bannerId);
    }
    public function scopeGetByLocale($query, $locale = null)
    {
        if (empty($locale)) {
            return $query;
        }

        return $query->where((new BannerClickedCount)->getTable() . '.locale', $locale);
    }


    public function user(): belongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function pageContent(): belongsTo
    {
        return $this->belongsTo(\App\Models\PageContent::class);
    }

    public function banner(): belongsTo
    {
        return $this->belongsTo(\App\Models\Banner::class);
    }

    public function scopeGetByCreatedAt($query, $filterCreatedAt= null, string $sign= null)
    {
        if (!empty($filterCreatedAt)) {
            if (!empty($sign)) {
                if (in_array($sign, ['=', '<', '>', '<=', '>=', '!-', '<>'])) {
                    $query->whereRaw($this->table . '.created_at ' . $sign . ' ?', [$filterCreatedAt]);
                }
            } else {
                $query->where($this->table.'.created_at', $filterCreatedAt);
            }
        }
        return $query;
    }

}
