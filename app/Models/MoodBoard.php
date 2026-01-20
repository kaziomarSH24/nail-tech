<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MoodBoard extends Model
{
    protected $guarded = ['id'];
    public function images()
    {
        return $this->hasMany(MoodBoardImage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //cover image attribute
    public function getCoverImageAttribute($value)
    {
       return $value ? Storage::disk('public')->url($value) : null;
    }
    
}
