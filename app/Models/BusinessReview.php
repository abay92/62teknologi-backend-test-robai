<?php

namespace App\Models;

class BusinessReview extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'comment',
        'star_rating',
        'is_active'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
