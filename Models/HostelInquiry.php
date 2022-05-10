<?php

namespace App\Models;
use DB;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HostelInquiry extends Model
{
    protected $table      = 'hostel_inquiries';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [ 'hostel_id', 'creator_id', 'hostel_room_id', 'email', 'full_name' , 'phone' , 'info' , 'start_date' , 'end_date' , 'request_callback', 'updated_at' ];

    private static $hostelInquiryStatusLabelValueArray = ['N' => 'New', 'A' => 'Accepted', 'O' => 'Completed', 'C' => 'Cancelled'];
    private static $hostelInquiryRequestCallbackLabelValueArray = Array('Y' => 'Yes', 'N' => 'No');

    protected static function boot() {
        parent::boot();
        static::deleting(function($hostel) {
            foreach ( $hostel->hostelInquiryOperations()->get() as $nextHostelOperation ) {
                $nextHostelOperation->delete();
            }
        });
    }


    public static function getHostelInquiryStatusValueArray($key_return = true): array
    {
        $resArray = [];
        foreach (self::$hostelInquiryStatusLabelValueArray as $key => $value) {
            if ($key_return) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }
        return $resArray;
    }
    public static function getHostelInquiryStatusLabel(string $status): string
    {
        if ( ! empty(self::$hostelInquiryStatusLabelValueArray[$status])) {
            return self::$hostelInquiryStatusLabelValueArray[$status];
        }
        return self::$hostelInquiryStatusLabelValueArray[0];
    }

    public static function getRequestCallbackValueArray($key_return = true): array
    {
        $resArray = [];
        foreach (self::$hostelInquiryRequestCallbackLabelValueArray as $key => $value) {
            if ($key_return) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }
        return $resArray;
    }
    public static function getRequestCallbackLabel(string $status): string
    {
        if ( ! empty(self::$hostelInquiryRequestCallbackLabelValueArray[$status])) {
            return self::$hostelInquiryRequestCallbackLabelValueArray[$status];
        }
        return self::$hostelInquiryRequestCallbackLabelValueArray[0];
    }

    public function scopeGetById($query, $id= null)
    {
        if (empty($id)) {
            return $query;
        }
        return $query->where(with(new HostelInquiry)->getTable().'.id', $id);
    }


    public function hostel()
    {
        return $this->belongsTo('App\Models\Hostel');
    }

    public function creator()
    {
        return $this->belongsTo('App\Models\User','creator_id');
    }

    public function hostelInquiryOperations()
    {
        return $this->hasMany('App\Models\HostelInquiryOperation');
    }
    /**
     * @property string $relative_data_info
     */
    public function getRelativeDataInfoAttribute()
    {
        $ret_text = 'Period from ' . \App\Library\Services\DateFunctionality::getFormattedDateTime($this->start_date).' till '.\App\Library\Services\DateFunctionality::getFormattedDateTime($this->end_date);
        $ret_text .=  ', creator email ' . $this->email;
        $ret_text .=  ', creator full name ' . $this->full_name;
        $ret_text .=  ', creator phone ' . $this->phone;
        $ret_text .=  ', info ' . $this->info;
        if ($this->request_callback) {
            $ret_text .=  ', Request callback ';
        }
        if (isset($this->hostel_inquiry_operations_count)) {
            $ret_text .= ', '.($this->hostel_inquiry_operations_count . pluralize3($this->hostel_inquiry_operations_count, ' no operations',
                        ' operation', ' operations')) . ', ';
        }
        if (isset($this->created_at)) {
            $ret_text .= ', created at ' . \App\Library\Services\DateFunctionality::getFormattedDateTime($this->created_at);
        }
        return ( ! empty($ret_text)) ? 'Has ' . trim($ret_text) : '';
    }


    public function scopeGetByCreatorId($query, $creator_id= null)
    {
        if (!empty($creator_id)) {
            if ( is_array($creator_id) ) {
                $query->whereIn(with(new HostelInquiry)->getTable().'.creator_id', $creator_id);
            } else {
                $query->where(with(new HostelInquiry)->getTable().'.creator_id', $creator_id);
            }
        }
        return $query;
    }

    public function scopeGetByHostelId($query, $hostel_id= null)
    {
        if (!empty($hostel_id)) {
            if ( is_array($hostel_id) ) {
                $query->whereIn(with(new HostelInquiry)->getTable().'.hostel_id', $hostel_id);
            } else {
                $query->where(with(new HostelInquiry)->getTable().'.hostel_id', $hostel_id);
            }
        }
        return $query;
    }

    public function scopeGetByStatus($query, $status= null)
    {
        if (!empty($status)) {
            if ( is_array($status) ) {
                $query->whereIn(with(new HostelInquiry)->getTable().'.status', $status);
            } else {
                $query->where(with(new HostelInquiry)->getTable().'.status', $status);
            }
        }
        return $query;
    }

    public function hostelRoom()
    {
        return $this->belongsTo('App\Models\HostelRoom');
    }


    public function getStartDateAttribute($date)
    {
        return Carbon::parse($date);
    }

    public function getEndDateAttribute($date)
    {
        return Carbon::parse($date);
    }

    public static function getHostelInquiryValidationRulesArray($hostel_inquiry_id = null): array
    {

        $validationRulesArray = [
            'full_name'          => [
                'required',
                'string',
                'max:100',
            ],
            'email'                => 'required|max:50|email',
            'phone'                => 'required|max:255',
            'info'                 => 'required',
            'hostel_room_id'       => 'required|exists:' . (with(new HostelRoom())->getTable()) . ',id',
            'start_date'           => 'required|date',
            'end_date'             => 'required|date|after_or_equal:start_date',
            'request_callback'     => 'nullable|in:' . getValueLabelKeys(HostelInquiry::getRequestCallbackValueArray(false)),
            'status'               => 'required|in:' . getValueLabelKeys(HostelInquiry::getHostelInquiryStatusValueArray(false)),
        ];
        return $validationRulesArray;
    }

    public static function getHostelInquiryValidationMessagesArray() : array
    {
        return [
            'hostel_id.required' => 'Hostel is required',
            'hostel_room_id.required' => 'Hostel room is required',
            'full_name.required'=>'Contact person full_name is required',
            'email.required'=>'Email is required',
            'email.email'=>'Email has invalid format',
            'phone.required'=>'Phone is required',
            'info.required'=>'Info is required',
            'start_date.required'=>'Start date is required',
            'end_date.required'=>'End date is required',
            'request_callback.required'=>'Request_callback is required',
            'status.required'=>'Status is required',
        ];
    }


}
