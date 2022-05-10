<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Enums\CheckValueType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Settings extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'settings';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'value',
        'updated_at',
    ];

    public static function scopeGetByName($query, $name= '')
    {
        if(!empty($name)) {
            return $query->where(with(new Settings)->getTable() . '.name', '=', $name);
        }
        return $query;
    }

    public static function getSettingsList($name= '')
    {
        $settingsValuesList = Settings
            ::orderBy('id', 'asc')
            ->getByName($name)
            ->select('id','name','value')
            ->get();

        return $settingsValuesList;
    }

    public static function getValue($name, int $checkValueType = null, $default_value = null)
    {
        $settingsValue = Settings::getByName($name)->first();
        if (empty($settingsValue->value)) {
            return $default_value;
        }

        if ($checkValueType == CheckValueType::cvtInteger and ! isValidInteger($settingsValue->value) and ! empty
            ($default_value)) {
            return $default_value;
        }
        if ($checkValueType == CheckValueType::cvtFloat and ! isValidFloat($settingsValue->value) and ! empty($default_value)) {
            return $default_value;
        }
        if ($checkValueType == CheckValueType::cvtBool and ! isValidBool($settingsValue->value) and ! empty($default_value)) {
            return $default_value;
        }

        return $settingsValue->value;
    }


    public static function getValidationRulesArray(): array
    {
        $returnValidationRules = [
            'base_currency'          => 'required',

            'items_per_page'         => 'required|integer',
            'backend_items_per_page' => 'required|integer',
            'rate_decimal_numbers'   => 'required|integer|min:1|max:12',

            'site_name'   => 'required|string|min:5|max:255',
            'site_heading'   => 'required|string|min:5|max:255',
            'copyright_text'   => 'required|string|min:5|max:255',
        ];

        return $returnValidationRules;
    }

}
