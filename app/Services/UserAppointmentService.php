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

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }


    // Define allowed filters
    protected function getAllowedFilters(): array
    {
        return [
            'appointment_at',
            AllowedFilter::exact('status'),
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
        return [
            'id',
            'appointment_at',
            'created_at',
        ];
    }
}
