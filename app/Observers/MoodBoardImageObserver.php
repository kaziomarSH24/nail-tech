<?php

namespace App\Observers;

class MoodBoardImageObserver extends BaseObserver
{
    protected array $relatedCacheTags = ['mood_boards'];
}
