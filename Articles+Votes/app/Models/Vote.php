<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\belongsToMany;

use Illuminate\Support\Arr;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

class Vote extends Model implements HasMedia, Sitemapable
{
    use HasFactory;
    use Sluggable;
    use HasTags;
    use InteractsWithMedia;

    protected $table = 'votes';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function toSitemapTag(): Url|string|array
    {
        return route('frontend.vote.show', $this);
    }

    protected $casts
        = [
            'meta_keywords' => 'array'
        ];

    protected $fillable
        = [
            'name',
            'description',
            'creator_id',
            'vote_category_id',
            'is_quiz',
            'is_homepage',
            'status',
            'ordering',
            'meta_description',
            'meta_keywords'
        ];

    // . Guarded attributes are used to specify those fields which are not mass assignable.
    protected $guarded = ['created_at'];

    public function lastModified(): Attribute
    {
        $lastModifiedValue = ! empty($this->updated_at) ? $this->updated_at : $this->created_at;

        return Attribute::make(get: fn($value) => $lastModifiedValue);
    }

    public const STATUS_NEW = 'N';
    public const STATUS_ACTIVE = 'A';
    public const STATUS_INACTIVE = 'I';

    private static $statusSelectionItems
        = [
            self::STATUS_NEW      => 'New (Draft)',
            self::STATUS_ACTIVE   => 'Active',
            self::STATUS_INACTIVE => 'Inactive'
        ];

    public static function getStatusSelectionItems(bool $keyReturn = true): array
    {
        $resArray = [];
        foreach (self::$statusSelectionItems as $key => $value) {
            if ($keyReturn) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }

        return $resArray;
    }

    public static function getStatusLabel(string $status): string
    {
        if ( ! empty(self::$statusSelectionItems[$status])) {
            return self::$statusSelectionItems[$status];
        }

        return self::$statusSelectionItems[self::STATUS_NEW];
    }

    private static $isQuizLabelValueArray = array(1 => 'Is Quiz', 0 => 'Is Not Quiz');
    private static $isHomepageLabelValueArray = array(1 => 'Is Homepage', 0 => 'Is Not Homepage');


    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    public function scopeGetById($query, $id)
    {
        return $query->where($this->table . '.id', $id);
    }

    public function scopeGetCreatorId($query, $creatorId)
    {
        return $query->where($this->table . '.creator_id', $creatorId);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function voteCategory(): BelongsTo
    {
        return $this->belongsTo(VoteCategory::class);
    }

    public function voteItems(): HasMany
    {
        return $this->hasMany(VoteItem::class);
    }

    public function quizQualityResults(): HasMany
    {
        return $this->hasMany(QuizQualityResult::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_vote', 'vote_id')
            ->withTimestamps()
            ->withPivot(['active', 'expired_at', 'supervisor_id', 'supervisor_notes']);
    }

    public function scopeGetByVoteCategoryId($query, $voteId = null)
    {
        if (empty($voteId)) {
            return $query;
        }

        return $query->where($this->table . '.vote_category_id', $voteId);
    }

    public function scopeGetByStatus($query, $status = null)
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where($this->table . '.status', $status);
    }

    public function scopeGetByIsQuiz($query, $isQuiz = null)
    {
        if ( ! isset($isQuiz)) {
            return $query;
        }

        return $query->where($this->table . '.is_quiz', $isQuiz);
    }

    public function scopeGetByIsHomepage($query, $isHomepage = null)
    {
        if ( ! isset($isHomepage)) {
            return $query;
        }

        return $query->where($this->table . '.is_homepage', $isHomepage);
    }

    public function scopeGetByName($query, $name = null, $partial = false)
    {
        if (empty($name)) {
            return $query;
        }

        return $query->where(
            $this->table . '.name',
            (! $partial ? '=' : 'like'),
            ($partial ? '%' : '') . $name . ($partial ? '%' : '')
        );
    }

    public function scopeGetBySlug($query, $slug = null)
    {
        if (empty($slug)) {
            return $query;
        }

        return $query->where($this->table . '.slug', $slug);
    }

    public static function getIsQuizValueArray($keyReturn = true): array
    {
        $resArray = [];
        foreach (self::$isQuizLabelValueArray as $key => $value) {
            if ($keyReturn) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }

        return $resArray;
    }

    public static function getIsQuizLabel(string $isQuiz): string
    {
        if ( ! empty(self::$isQuizLabelValueArray[$isQuiz])) {
            return self::$isQuizLabelValueArray[$isQuiz];
        }

        return self::$isQuizLabelValueArray[0];
    }

    public static function getIsHomepageValueArray($keyReturn = true): array
    {
        $resArray = [];
        foreach (self::$isHomepageLabelValueArray as $key => $value) {
            if ($keyReturn) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }

        return $resArray;
    }

    public static function getIsHomepageLabel(string $isHomepage): string
    {
        if ( ! empty(self::$isHomepageLabelValueArray[$isHomepage])) {
            return self::$isHomepageLabelValueArray[$isHomepage];
        }

        return self::$isHomepageLabelValueArray[0];
    }

    public static function getValidationRulesArray($voteId = null, array $skipFieldsArray = []): array
    {
        $table                = (new Vote)->getTable();
        $validationRulesArray = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($table)->ignore($voteId),
            ],

            'description'      => 'required',
            'creator_id'       => 'required|integer|exists:' . ((new User)->getTable()) . ',id',
            'vote_category_id' => 'required|integer|exists:' . ((new VoteCategory)->getTable()) . ',id',

            'is_quiz'     => 'required|boolean',
            'is_homepage' => 'required|boolean',
            'status'      => 'required|in:' . getValueLabelKeys(Vote::getStatusSelectionItems(false)),
            'ordering'    => 'nullable|integer',
        ];

        foreach ($skipFieldsArray as $next_field) {
            if ( ! empty($validationRulesArray[$next_field])) {
                $validationRulesArray = Arr::except($validationRulesArray, $next_field);
            }
        }

        return $validationRulesArray;
    }

    public static function getValidationMessagesArray(): array
    {
        return [
            'name.required'             => 'Name is required',
            'name.unique'               => 'Any vote must have unique title',
            'description.required'      => 'Description is required',
            'vote_category_id.required' => 'Vote category is required',
            'is_quiz.required'          => 'Is quiz is required',
            'is_homepage.required'      => 'Is homepage is required',
            'status.required'           => 'Status is required',
            'ordering.required'         => 'Ordering is required',
            'ordering.invalid'          => 'Ordering is invalid. Must be valid integer',
        ];
    }

}
