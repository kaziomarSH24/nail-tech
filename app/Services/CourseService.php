<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\Course;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filters\GlobalSearchFilter;

class CourseService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Course::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }


    // Define allowed filters
     protected function getAllowedFilters(): array
    {
        return [
            'title',
            AllowedFilter::exact('is_published'),
        ];
    }

    // Define allowed includes relationships
     protected function getAllowedIncludes(): array
     {
        return [
            'lessons',
        ];
     }

     // Define allowed sorts
     protected function getAllowedSorts(): array
     {
        return [
            'id',
            'created_at',
        ];
     }
}
