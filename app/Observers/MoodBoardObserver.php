<?php

namespace App\Observers;

class MoodBoardObserver extends BaseObserver
{
    protected array $relatedCacheTags = ['mood_board_images'];
}
