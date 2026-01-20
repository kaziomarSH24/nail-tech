<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoodBoardImage extends Model
{
    protected $guarded = ['id'];

    public function board()
    {
        return $this->belongsTo(MoodBoard::class, 'mood_board_id');
    }
}
