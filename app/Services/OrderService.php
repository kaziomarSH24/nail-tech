<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\Order;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filters\GlobalSearchFilter;

class OrderService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Order::class;
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
           
        ];
    }

    // Define allowed includes relationships
     protected function getAllowedIncludes(): array
     {
        return [
            'user',
        ];
     }

     // Define allowed sorts
     protected function getAllowedSorts(): array
     {
        return [
            'id',
            'name',
            'created_at',
        ];
     }

     //**Custom Logic */


}
