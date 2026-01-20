<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\UserAppointment;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filters\GlobalSearchFilter;

class UserAppointmentService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = UserAppointment::class;
    protected bool $cachePerUser = true;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }


    // Define allowed filters
    protected function getAllowedFilters(): array
    {
        return [
            'client_name',
            AllowedFilter::exact('status'),
            //filter[date]=2025-01-01
            AllowedFilter::scope('date', 'date'),
        ];
    }

    // Define allowed includes relationships
    protected function getAllowedIncludes(): array
    {
        return [
            //
        ];
    }

    // Define allowed sorts
    protected function getAllowedSorts(): array
    {
        return ['appointment_at', 'created_at'];
    }
}
