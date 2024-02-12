<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Cviebrock\EloquentSluggable\Sluggable;
use \Cviebrock\EloquentSluggable\Services\SlugService;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Library\Rules\RulesImageUploading;
use App\Enums\UploadImageRules;
use App\Enums\UploadImageRulesParameter;

/**
 * App\Models\Page
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string|null $content_shortly
 * @property int $creator_id
 * @property int $is_homepage
 * @property int $published
 * @property string $price
 * @property string|null $meta_description
 * @property array|null $meta_keywords
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $category
 * @property-read int|null $category_count
 * @property-read \App\Models\User|null $creator
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PageCategory[] $pageCategories
 * @property-read int|null $page_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PageRevision[] $pageRevisions
 * @property-read int|null $page_revisions_count
 * @method static \Database\Factories\PageFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Page findSimilarSlugs(string $attribute, array $config, string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder|Page getById($id)
 * @method static \Illuminate\Database\Eloquent\Builder|Page getByIsHomepage($is_homepage = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Page getByPublished($published = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Page getBySlug($slug = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Page getByTitle($title = null, $partial = false)
 * @method static \Illuminate\Database\Eloquent\Builder|Page getExtendedSearch($s = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Page newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Page newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Page onlyPublished()
 * @method static \Illuminate\Database\Eloquent\Builder|Page query()
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereContentShortly($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereIsHomepage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereMetaKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page wherePublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page withUniqueSlugConstraints(\Illuminate\Database\Eloquent\Model $model, string $attribute, array $config, string $slug)
 * @mixin \Eloquent
 */
class Page extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;
    use Sluggable;

    protected $table = 'pages';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $dates = ['created_at', 'updated_at'];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable
        = [
            'title',
            'slug',
            'content',
            'content_shortly',
            'creator_id',
            'is_homepage',
            'price',
            'published',
            'meta_description',
            'meta_keywords'
        ];

    protected $casts
        = [
            'meta_keywords' => 'array',
        ];

    private static $isHomepageLabelValueArray = ['1' => 'Yes, homepage', '0' => 'No'];

    public static function getIsHomepageValueArray($keyReturn = true): array
    {
        if ( ! $keyReturn) {
            return self::$isHomepageLabelValueArray;
        }
        $resArray = [];
        foreach (self::$isHomepageLabelValueArray as $key => $value) {
            if ($keyReturn) {
                $resArray[] = ['key' => $key, 'label' => $value];
            }
        }

        return $resArray;
    }

    public static function getIsHomepageLabel(string $has_locations): string
    {
        if ( ! empty(self::$isHomepageLabelValueArray[$has_locations])) {
            return self::$isHomepageLabelValueArray[$has_locations];
        }

        return self::$isHomepageLabelValueArray[0];
    }


    private static $publishedLabelValueArray = ['1' => 'Yes, published', '0' => 'No'];

    public static function getPublishedValueArray($keyReturn = true): array
    {
        if ( ! $keyReturn) {
            return self::$publishedLabelValueArray;
        }
        $resArray = [];
        foreach (self::$publishedLabelValueArray as $key => $value) {
            if ($keyReturn) {
                $resArray[] = ['key' => $key, 'label' => $value];
            }
        }

        return $resArray;
    }

    public static function getPublishedLabel(string $published): string
    {
        if ( ! empty(self::$publishedLabelValueArray[$published])) {
            return self::$publishedLabelValueArray[$published];
        }

        return self::$publishedLabelValueArray[0];
    }


    public function creator()
    {
        return $this->hasOne('App\Models\User', 'creator_id', 'id');
    }

    public function pageRevisions()
    {
        return $this->hasMany('App\Models\PageRevision', 'page_id', 'id');
    }

    public function pageCategories()
    {
        return $this->hasMany('App\Models\PageCategory', 'page_id', 'id');
    }

    public function category()
    {
        return $this->HasManyThrough(
            'App\Models\Category',
            'App\Models\PageCategory',
            'page_id',
            'id',
            'id',
            'category_id',
        );
    }

    public function scopeGetById($query, $id)
    {
        return $query->where(with(new Page)->getTable() . '.id', $id);
    }

    public function scopeGetByTitle($query, $title = null, $partial = false)
    {
        if (empty($title)) {
            return $query;
        }

        return $query->where(with(new Page)->getTable() . '.title', (! $partial ? '=' : 'like'),
            ($partial ? '%' : '') . $title . ($partial ? '%' : ''));
    }

    public function scopeGetExtendedSearch($query, $s = null)
    {
        if (empty($s)) {
            return $query;
        }
        $prefix = DB::getTablePrefix();
        $tb     = with(new Page)->getTable() . ($prefix ?? $prefix) . '.';

        return $query->whereRaw(" ( " . $tb . "title like '%" . $s . "%' " . ' OR ' .
                                $tb . "content like '%" . $s . "%' " . ' OR ' .
                                $tb . "meta_description like '%" . $s . "%' " . " ) ");
    }


    public function scopeGetBySlug($query, $slug = null)
    {
        if (empty($slug)) {
            return $query;
        }

        return $query->where(with(new Page)->getTable() . '.slug', $slug);
    }


    public function scopeGetByIsHomepage($query, $is_homepage = null)
    {
        if ( ! isset($is_homepage) or strlen($is_homepage) == 0) {
            return $query;
        }

        return $query->where(with(new Page)->getTable() . '.is_homepage', $is_homepage);
    }

    public function scopeGetByPublished($query, $published = null)
    {
        if ( ! isset($published) or strlen($published) == 0) {
            return $query;
        }

        return $query->where(with(new Page)->getTable() . '.published', $published);
    }

    public function scopeOnlyPublished($query)
    {
        return $query->where('published', true);
    }

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($page) {
            foreach ($page->getMedia(config('app.media_app_name')) as $mediaImage) {
                $mediaImage->delete();
            }
        });

        static::updating(function ($pageItem) {
            $currentPage = Page
                ::getById($pageItem->id)
                ->first();
            if ( ! empty($currentPage)) {
                if ($currentPage->title != $pageItem->title) {
                    $slug           = SlugService::createSlug(Page::class, 'slug', $pageItem->title);
                    $pageItem->slug = Str::slug($slug);
                }
            }
        });
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = workTextString($value);
    }

    public static function getPageValidationRulesArray(
        $pageId = null,
        $creatorId = null,
        array $skipFieldsArray = []
    ): array {
        $coverImageUploadingRules = new RulesImageUploading(UploadImageRules::UIR_PAGE_COVER_IMAGE);
        $coverImageRules          = $coverImageUploadingRules->getRules();

        $documentUploadingRules = new RulesImageUploading(UploadImageRules::UIR_DOCUMENT);
        $documentRules          = $documentUploadingRules->getRules();

        $additionalTitleValidationRule = 'check_page_unique_by_creator_id:' . $creatorId . ',' . (! empty($pageId) ? $pageId : '');
        $validationRulesArray          = [
            'title'            => 'required|string|max:255|' . $additionalTitleValidationRule,
            'content'          => 'required',
            'content_shortly'  => 'nullable',
            'meta_description' => 'nullable',
            'meta_keywords'    => 'nullable',
            'published'        => ['boolean', 'nullable'],
            'is_homepage'      => ['boolean', 'nullable'],
            'creator_id'       => 'required|integer|exists:' . (with(new User)->getTable()) . ',id',
        ];
        $validationRulesArray          = Arr::add($validationRulesArray, 'image', $coverImageRules);
        $validationRulesArray          = Arr::add($validationRulesArray, 'document', $documentRules);

        foreach ($skipFieldsArray as $nextField) {
            if ( ! empty($validationRulesArray[$nextField])) {
                unset($validationRulesArray[$nextField]);
            }
        }

        return $validationRulesArray;
    } // public static function getPageValidationRulesArray($pageId) : array


    public static function getValidationMessagesArray(): array
    {
        $coverImageUploadingRules     = new RulesImageUploading(UploadImageRules::UIR_PAGE_COVER_IMAGE);
        $coverImageUploadedFileMaxMib = $coverImageUploadingRules->getRuleParameterValue(UploadImageRulesParameter::UIRPV_MAX_SIZE_IN_BYTES);
        $coverImageUploadedFileMimes = $coverImageUploadingRules->getRuleParameterValue(UploadImageRulesParameter::UIRPV_ACCEPTABLE_FILE_MIMES);
        $coverImageUploadedFileDimensionsMaxWidth = $coverImageUploadingRules->getRuleParameterValue(UploadImageRulesParameter::UIRPV_DIMENSIONS_MAX_WIDTH);

        $documentUploadingRules     = new RulesImageUploading(UploadImageRules::UIR_DOCUMENT);
        $documentUploadedFileMaxMib = $documentUploadingRules->getRuleParameterValue(UploadImageRulesParameter::UIRPV_MAX_SIZE_IN_BYTES);
        $documentUploadedFileMimes = $documentUploadingRules->getRuleParameterValue(UploadImageRulesParameter::UIRPV_ACCEPTABLE_FILE_MIMES);
        return [
            'title.unique'                    => 'Title is not unique',
            'check_page_unique_by_creator_id' => 'Page Title must be unique for any user',
            'title.required'                  => 'Title is required',
            'content.required'                => 'Content is required',
            'creator_id.required'             => 'Creator is required',
            'image.max'                       => 'Selected image is too big in size. It exceeds image size limit in ' .
                                                 getCFFileSizeAsString($coverImageUploadedFileMaxMib * 1024/** 1024*/),
            'image.dimensions'                => 'Selected image is too big in width. Max acceptable width : ' .
                                                 $coverImageUploadedFileDimensionsMaxWidth . 'px',
            'image.validation.uploaded'       => 'Invalid image is selected',
            'image.uploaded'                  => 'Invalid image is selected. Check it must be not bigger ' .
                                                 getCFFileSizeAsString($coverImageUploadedFileMaxMib * 1024/** 1024*/),
            'image.mimes'                     => 'Invalid format of image. Acceptable formats are : ' . $coverImageUploadedFileMimes,

            'document.max'                 => 'Selected document is too big in size. It exceeds document size limit in ' .
                                              getCFFileSizeAsString($documentUploadedFileMaxMib * 1024/** 1024*/),
            'document.validation.uploaded' => 'Invalid document is selected',
            'document.uploaded'            => 'Invalid document is selected. Check it must be not bigger ' .
                                              getCFFileSizeAsString($documentUploadedFileMaxMib * 1024/** 1024*/),
            'document.mimes'               => 'Invalid format of document. Acceptable formats are : ' . $documentUploadedFileMimes,
        ];
    }

    public static function getSimilarPageByCreatorId(
        string $title,
        int $creatorId,
        int $id = null,
        $returnCount = false
    ) {
        $quoteModel = Page::where('title', $title);
        $quoteModel = $quoteModel->where('creator_id', '=', $creatorId);
        if ( ! empty($id)) {
            $quoteModel = $quoteModel->where('id', '!=', $id);
        }

        if ($returnCount) {
            return $quoteModel->get()->count();
        }
        $retRow = $quoteModel->get();
        if (empty($retRow[0])) {
            return false;
        }

        return $retRow[0];
    }

}
