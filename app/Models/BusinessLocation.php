<?php

namespace App\Models;

class BusinessLocation extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'address1',
        'address2',
        'address3',
        'country',
        'state',
        'city',
        'zip_code',
        'latitude',
        'longitude'
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($m) {
            $alias = str_replace(' ', '-', $m->business->name) . '-' . str_replace(' ', '-', $m->city);
            $m->business->alias = str_replace('--', '-', $alias);
            $m->business->save();
        });

        self::updating(function ($m) {
            $alias = str_replace(' ', '-', $m->business->name) . '-' . str_replace(' ', '-', $m->city);
            $m->business->alias = str_replace('--', '-', $alias);
            $m->business->save();
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
