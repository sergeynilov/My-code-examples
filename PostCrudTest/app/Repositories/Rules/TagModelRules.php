<?php

namespace App\Repositories\Rules;

use App\Models\Tag;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class TagModelRules
{
    /*
    return validation rules array for tag model
      *
      @param array - $skipFieldsArray - which fields must be skipped from array
      *
      * @returns array
      *
    */
    public static function getValidationRulesArray($tagId = null, array $skipFieldsArray = []): array
    {
        $validationRulesArray = [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique((new Tag)->getTable())->ignore($tagId),
            ],
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
            'title.required' => 'Title is required 987',
        ];
    }
}
