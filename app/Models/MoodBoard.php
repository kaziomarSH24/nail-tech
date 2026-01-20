<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoodBoard extends Model
{
    protected $guarded = ['id'];
    public function images()
    {
        return $this->hasMany(MoodBoardImage::class);
    }
}
