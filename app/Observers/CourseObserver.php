<?php

namespace App\Observers;

class CourseObserver extends BaseObserver
{
     protected array $relatedCacheTags = ['lessons'];
}
