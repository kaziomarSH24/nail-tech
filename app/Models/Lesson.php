<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
    protected $guarded = [];
    protected $appends = ['video_url'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function getVideoUrlAttribute()
    {
        if ($this->video_type === 'url') {
            return $this->video_path;
        }

        // Local/S3 Storage Link
        return Storage::disk(config('filesystems.default'))->url($this->video_path);
    }
}
