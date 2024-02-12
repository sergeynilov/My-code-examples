<?php

namespace App\Repositories\Rules;

use App\Models\Language;
use App\Models\Post;
use Illuminate\Support\Arr;

/*
Class-container for rules/messages of posts validation
*
*/
class PostModelRules
{
    /*
     return validation rules array for post model
     *
      @param int - $postId - ID of post
      *
      @param array - $skipFieldsArray - which fields must be skipped from array
      *
      * @returns array
      *
    */
    public static function getValidationRulesArray($postId = null, array $skipFieldsArray = []): array
    {
        $validationRulesArray = [
            'post_id' => 'required|exists:' . ((new Post)->getTable()) . ',id',
            'language_id' => 'required|exists:' . ((new Language)->getTable()) . ',id',
            'media_id' => 'string|nullable|max:20',
            'media_file_to_upload' => 'array|nullable',
            'title' => 'string|required|max:255',
            'description' => 'string|required',
            'content' => 'string|required',
        ];

        foreach ($skipFieldsArray as $next_field) {
            if (! empty($validationRulesArray[$next_field])) {
                $validationRulesArray = Arr::except($validationRulesArray, $next_field);
            }
        }

        return $validationRulesArray;
    }

    /*
     * Returns custom Messages for validation errors
      *
      * @returns array
      *
    */
    public static function getValidationMessagesArray(): array
    {
        return [
            'title.required' => 'Title is required 12',
            'description.required' => 'Description is required 987',
            'content.required' => 'Content is required 12',
            'language_id.invalid' => 'language_id is invalid. Must be valid reference to languages table98',
            'post_id.invalid' => 'post_id is invalid. Must be valid reference to posts table98',
        ];
    }
}
