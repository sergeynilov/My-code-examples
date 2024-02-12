<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserQuizRequest extends Model
{
    protected $table      = 'user_quiz_requests';
    protected $primaryKey = 'id';
    public $timestamps    = true;
    protected $fillable   = ['quiz_category_id', 'user_name', 'user_quiz_request_id', 'user_email', 'is_passed',
                             'selected_locale', 'time_spent', 'summary_of_points', 'expires_at', 'hashed_link'];
    protected $casts = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public const IS_PASSED_YES = 1;
    public const IS_PASSED_NO  = 0;

    private static $isPassedSelectionItems
        = [
            self::IS_PASSED_NO  => 'Not passed',
            self::IS_PASSED_YES => 'Is passed',
        ];

    public static function getIsPassedSelectionItems(bool $keyReturn = true): array
    {
        $resArray = [];
        foreach (self::$isPassedSelectionItems as $key => $value) {
            if ($keyReturn) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }

        return $resArray;
    }

    public static function getIsPassedLabel(string $isPassed): string
    {
        if (! empty(self::$isPassedSelectionItems[$isPassed])) {
            return self::$isPassedSelectionItems[$isPassed];
        }

        return self::$isPassedSelectionItems[self::IS_PASSED_NO];
    }

    public function scopeGetOnlyNotPassed($query)
    {
        $query->where($this->table.'.is_passed', false);
    }

    public function scopeGetByQuizCategoryId($query, int $quizCategoryId = null)
    {
        if (! empty($quizCategoryId)) {
            $query->where($this->table.'.quiz_category_id', $quizCategoryId);
        }

        return $query;
    }

    public function quizCategory(): BelongsTo
    {
        return $this->belongsTo(QuizCategory::class);
    }


    public function scopeGetById($query, $id)
    {
        return $query->where($this->table.'.id', $id);
    }
    public function scopeGetByHashedLink($query, $hashedLink)
    {
        return $query->where($this->table.'.hashed_link', $hashedLink);
    }


    public function userMeetings(): HasMany
    {
        return $this->hasMany(UserMeeting::class);
    }

    public function onlyCancelledUserMeetings(): HasMany
    {
        return $this->hasMany(UserMeeting::class)->where('status',UserMeeting::USER_MEETING_STATUS_CANCELLED );
    }

}
