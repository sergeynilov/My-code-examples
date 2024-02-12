<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Kenepa\ResourceLock\Models\Concerns\HasLocks;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Casts\MoneyCast;
use Spatie\Translatable\HasTranslations;

class Banner extends Model implements HasMedia
{
    use HasLocks;
    use InteractsWithMedia;
    use HasTranslations;

    protected $table = 'banners';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable
        = [
            'text',
            'description',
            'url',
            'active',
            'creator_id',
            'currency',
            'monthly_support_price',
            'year_support_price',
            'ordering',
            'expires_at',
            'banner_category_id',
            'banner_bgimage_id',
            'updated_at'
        ];
    public $translatable = ['text', 'description'];

    protected $casts
        = [
            'active'                => 'boolean',
            'expires_at'            => 'date',
            'updated_at'            => 'datetime',
            'created_at'            => 'datetime',
            'monthly_support_price' => MoneyCast::class,
            'year_support_price'    => MoneyCast::class,
        ];

    // . Guarded attributes are used to specify those fields which are not mass assignable.
    protected $guarded = ['created_at'];

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    private static $activeSelectionItems
        = [
            self::STATUS_ACTIVE   => 'Active',
            self::STATUS_INACTIVE => 'Inactive'
        ];

    public static function getActiveSelectionItems(bool $keyReturn = true): array
    {
        $resArray = [];
        foreach (self::$activeSelectionItems as $key => $value) {
            if ($keyReturn) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }

        return $resArray;
    }

    public static function getActiveLabel(string $active): string
    {
        if ( ! empty(self::$activeSelectionItems[$active])) {
            return self::$activeSelectionItems[$active];
        }

        return self::$activeSelectionItems[self::STATUS_INACTIVE];
    }

    public function scopeGetById($query, $id)
    {
        return $query->where($this->table . '.id', $id);
    }

    public function scopeGetByExpiresAt($query, $filterExpiresAtFrom = null, string $sign = null)
    {
        if ( ! empty($filterExpiresAtFrom)) {
            if ( ! empty($sign)) {
                $query->whereRaw($this->table . '.expires_at ' . $sign . "'" . $filterExpiresAtFrom . "' ");
            } else {
                $query->where($this->table . '.expires_at', $filterExpiresAtFrom);
            }
        }

        return $query;
    }


    public function scopeGetByActive($query, $active = null)
    {
        if ( ! isset($active)) {
            return $query;
        }

        return $query->where('active', (bool)$active);
    }

    public function scopeGetByCreatorId($query, $creatorId)
    {
        if ( ! empty($creatorId)) {
            return $query->where($this->table . '.creator_id', $creatorId);
        }
    }

    public function creator()
    {
        return $this->belongsTo('App\Models\User', 'creator_id', 'id');
    }

    public function scopeGetByText($query, $text = null, $partial = false)
    {
        if (empty($text)) {
            return $query;
        }

        return $query->where($this->table . '.text', (! $partial ? '=' : 'like'),
            ($partial ? '%' : '') . $text . ($partial ? '%' : ''));
    }

    public function bannerBgimage(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BannerBgimage::class);

    }

    public function bannerCategory(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BannerCategory::class);
    }

    public function bannerClickedCounts(): HasMany
    {
        return $this->hasMany(\App\Models\BannerClickedCount::class);
    }

    public function managedByUsers(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\User');
    }

}
