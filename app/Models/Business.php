<?php

namespace App\Models;

class Business extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'alias',
        'name',
        'image',
        'phone',
        'price',
        'url'
    ];

    public static function boot()
    {
        parent::boot();

        self::updated(function ($m) {
            $city = $m->location ? $m->location->city : null;
            $alias = str_replace(' ', '-', $m->name) . '-' . str_replace(' ', '-', $city);
            $m->alias = str_replace('--', '-', $alias);
        });

        self::deleting(function ($m) {
            $m->photos()->map(function ($item) {
                deleteFile($item->name);
            });
        });
    }

    public function location()
    {
        return $this->hasOne(BusinessLocation::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'business_categories', 'business_id', 'category_id');
    }

    public function photos()
    {
        return $this->hasMany(BusinessPhoto::class);
    }

    public function transactions()
    {
        return $this->hasMany(BusinessTransaction::class);
    }

    public function reviews()
    {
        return $this->hasMany(BusinessReview::class);
    }

    public function getImageUrlAttribute()
    {
        return getImage($this->image);
    }
}
