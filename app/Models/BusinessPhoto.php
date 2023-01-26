<?php

namespace App\Models;

class BusinessPhoto extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'image'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function getImageUrlAttribute()
    {
        return getImage($this->image);
    }
}
