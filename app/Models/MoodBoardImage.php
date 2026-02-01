<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MoodBoardImage extends Model
{
    protected $guarded = ['id'];

    public function board()
    {
        return $this->belongsTo(MoodBoard::class, 'mood_board_id');
    }

    //image_path attribute
    public function getImagePathAttribute($value)
    {
       return $value ? Storage::disk('public')->url($value) : null;
    }
}
