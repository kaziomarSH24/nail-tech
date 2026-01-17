<?php

namespace App\Observers;

class LessonsObserver extends BaseObserver
{
        protected array $relatedCacheTags = ['courses'];
}
