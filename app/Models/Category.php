<?php

namespace App\Models;

class Category extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'alias',
        'title'
    ];

    public function businesses()
    {
        return $this->belongsToMany(Business::class);
    }
}
