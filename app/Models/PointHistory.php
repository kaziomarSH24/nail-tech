<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointHistory extends Model
{
    protected $guarded = [];

    public function rewardable()
    {
        return $this->morphTo();
    }
}
