<?php

namespace App\Models;

class BusinessOpenTime extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'day',
        'start_time',
        'end_time'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
