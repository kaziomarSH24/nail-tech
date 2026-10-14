<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
    protected $guarded = [];
    protected $appends = ['video_url', 'image_url'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function getVideoUrlAttribute()
    {
        if (empty($this->video_path)) {
            return null;
        }

        if ($this->video_type === 'url') {
            return $this->video_path;
        }

        // Local/S3 Storage Link
        return Storage::disk(config('filesystems.default'))->url($this->video_path);
    }

    public function getImageUrlAttribute()
    {
        if (empty($this->image_path)) {
            return null;
        }

        return Storage::disk(config('filesystems.default'))->url($this->image_path);
    }
}
