<?php

namespace App\Models;

use DB;

use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Validation\Rule;

class ProductSubscription extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'ts_product_subscriptions';
    public $timestamps = true;
    protected $primaryKey = '_id';
    protected $touches = ['product'];

    protected $casts
        = [
            'created_at'   => 'datetime',
        ];

    protected $fillable = ['product_id', 'subscription_id'];

    public function scopeGetById($query, $id)
    {
        if (empty($id)) {
            return $query;
        }
        return $query->where('_id', $id);
    }

    public function scopeGetByProductId($query, $productId = null)
    {
        if ( !empty($productId)) {
            $query->where('product_id', $productId);
        }
        return $query;
    }
    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'product_id', '_id');
    }

    public function scopeGetBySubscriptionId($query, string $subscriptionId = null)
    {
        if ( !empty($subscriptionId)) {
            $query->where('subscription_id', $subscriptionId);
        }
        return $query;
    }
    public function subscription()
    {
        return $this->belongsTo('App\Models\Subscription', 'subscription_id', '_id');
    }

    public static function getValidationRulesArray(): array
    {
        $validationRulesArray = [
            'product_id'         => 'required|exists:' . (with(new Product)->getTable()) . ',id',
            'subscription_id' => 'required|exists:' . (with(new Subscription)->getTable()) . ',id',
        ];
        return $validationRulesArray;
    }
}
