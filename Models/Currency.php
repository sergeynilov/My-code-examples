<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Currency extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'currencies';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public $media_image_url = '';
    public $latest_currency_history = [];

    protected $fillable
        = [
            'name',
            'num_code',
            'description',
            'char_code',
            'color',
            'bgcolor',
            'is_top',
            'active',
            'ordering',
        ];

    private static $currencyIsTopLabelValueArray = array(0 => 'Not is top', 1 => 'Is top');

    public static function getCurrencyIsTopValueArray($key_return = true): array
    {
        $resArray = [];
        foreach (self::$currencyIsTopLabelValueArray as $key => $value) {
            if ($key_return) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }

        return $resArray;
    }

    public static function getCurrencyIsTopLabel(string $status): string
    {
        if ( ! empty(self::$currencyIsTopLabelValueArray[$status])) {
            return self::$currencyIsTopLabelValueArray[$status];
        }
        return '';
    }



    private static $currencyActiveLabelValueArray = array(0 => 'Inactive', 1 => 'Active');
    public static function getCurrencyActiveValueArray($key_return = true): array
    {
        $resArray = [];
        foreach (self::$currencyActiveLabelValueArray as $key => $value) {
            if ($key_return) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }

        return $resArray;
    }

    public static function getCurrencyActiveLabel(string $status): string
    {
        if ( ! empty(self::$currencyActiveLabelValueArray[$status])) {
            return self::$currencyActiveLabelValueArray[$status];
        }

        return '';
    }

    public function myMediaRelation()
    {
        return $this->media()->where('collection_name', config('app.media_app_name'));
    }

    public function latestCurrencyHistory()
    {
        return $this->hasMany('App\Models\CurrencyHistory')->latest();
    }

    public function currencyHistories()
    {
        return $this->hasMany('App\Models\CurrencyHistory', 'currency_id', 'id');
    }

    public function scopeGetById($query, $id)
    {
        if ( ! empty($id)) {
            if (is_array($id)) {
                $query->whereIn(with(new Currency)->getTable() . '.id', $id);
            } else {
                $query->where(with(new Currency)->getTable() . '.id', $id);
            }
        }

        return $query;
    }

    public function scopeGetByName($query, $name = null)
    {
        if (empty($name)) {
            return $query;
        }

        return $query->where(with(new Currency)->getTable() . '.name', 'like', '%' . $name . '%');
    }


    public function scopeGetByIsTop($query, $is_top = null)
    {
        if ( ! isset($is_top) or strlen($is_top) == 0) {
            return $query;
        }

        return $query->where('is_top', $is_top);
    }

    public function scopeGetByActive($query, $active = null)
    {
        if ( ! isset($active) or strlen($active) == 0) {
            return $query;
        }

        return $query->where('active', $active);
    }

    public function scopeGetByNumCode($query, $numCode = null)
    {
        if (empty($numCode)) {
            return $query;
        }

        return $query->where(with(new Currency)->getTable() . '.num_code', $numCode);
    }


    public function scopeExcludeCharCode($query, $charCode = null)
    {
        if (empty($charCode)) {
            return $query;
        }

        return $query->where(with(new Currency)->getTable() . '.char_code', '!=', $charCode);
    }

    public function scopeGetByCharCode($query, $charCode = null)
    {
        if (empty($charCode)) {
            return $query;
        }

        return $query->where(with(new Currency)->getTable() . '.char_code', $charCode);
    }


    public static function getCurrencyValidationRulesArray($currency_id = null, array $skipFieldsArray = []): array
    {
        $validationRulesArray = [
            'name'      => [
                'required',
                'string',
                'max:100',
                Rule::unique(with(new Currency)->getTable())->ignore($currency_id),
            ],
            'num_code'  => [
                'integer',
                'required',
                'string',
                'min:1',
                'max:999',
                Rule::unique(with(new Currency)->getTable())->ignore($currency_id),
            ],
            'description'  => [
                'string',
                'nullable'
            ],
            'char_code' => [
                'required',
                'string',
                'min:3',
                'max:3',
                Rule::unique(with(new Currency)->getTable())->ignore($currency_id),
            ],
            'color'     => [
                'required',
                'string',
                'min:7',
                'max:7',
            ],
            'bgcolor'   => [
                'required',
                'string',
                'min:7',
                'max:7',
            ],
            'is_top'    => 'nullable',
            'active'    => 'boolean',
            'ordering'  => 'required|integer|max:2000',

        ];
        foreach ($skipFieldsArray as $next_field) {
            if ( ! empty($validationRulesArray[$next_field])) {
                unset($validationRulesArray[$next_field]);
            }
        }

        return $validationRulesArray;
    }

    public static function getCurrenciesSelectionArray(): array
    {
        $currencies               = Currency
            ::orderBy('ordering', 'asc')
            ->get();
        $currenciesSelectionArray = [];
        foreach ($currencies as $nextCurrency) {
            $currenciesSelectionArray[] = [
                'char_code' => $nextCurrency->char_code,
                'name'      => $nextCurrency->id . '=>' . $nextCurrency->char_code . '=>' . $nextCurrency->name
            ];
        }

        return $currenciesSelectionArray;
    }


}
