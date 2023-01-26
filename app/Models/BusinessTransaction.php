<?php

namespace App\Models;

class BusinessTransaction extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'name'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
