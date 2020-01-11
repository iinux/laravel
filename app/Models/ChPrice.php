<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChPrice extends Model
{
    protected $guarded = [];
    
    const STATUS_NEW = 10;
    const STATUS_HISTORY = 20;
    
}
