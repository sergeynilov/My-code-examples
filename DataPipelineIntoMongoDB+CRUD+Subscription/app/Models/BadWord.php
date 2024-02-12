<?php

namespace App\Models;

use DB;

use Jenssegers\Mongodb\Eloquent\Model;

class BadWord extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'bad_words';
    public $timestamps = true;
    protected $primaryKey = '_id';

    protected $fillable = ['word'];

    protected $casts
        = [
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];

    public function scopeGetById($query, $id)
    {
        if (empty($id)) {
            return $query;
        }

        return $query->where('_id', $id);
    }

    public function scopeGetByWord($query, string $word = null)
    {
        if (empty($word)) {
            return $query;
        }

        return $query->where('word', 'like', '%' . $word . '%');
    }

    public static function getValidationRulesArray(string $wordId = null): array
    {
        $additionalWordValidationRule = 'checkBadWordUnique:' . $wordId;
        $validationRulesArray         = [
            'word' => [
                'required',
                'string',
                'max:255',
                'min:2',
                $additionalWordValidationRule
            ],
        ];

        return $validationRulesArray;
    }

    /* check if provided word is unique for badWord.word field */
    public static function getSimilarBadWordByWord(string $word, string $id = null, bool $returnCount = false)
    {
        $quoteModel = BadWord::where('word', 'like', $word);
        if ( ! empty($id)) {
            $quoteModel = $quoteModel->where('_id', '!=', $id);
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

    public static function getValidationMessagesArray(): array
    {
        return [
            'word.required'         => 'Word is required',
            'check_bad_word_unique' => 'Any word must be unique',
        ];
    }

}

