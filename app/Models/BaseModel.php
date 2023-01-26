<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FilterQuery;
use App\Traits\Uuids;

abstract class BaseModel extends Model
{
    use HasFactory;
    use Uuids;
    use FilterQuery;

    protected $primaryKey = 'id';

    public static function boot()
    {
        parent::boot();
        self::bootUsesGuid();
    }
}
