<?php

namespace App\Services;

use App\Filters\GlobalSearchFilter;
use App\Services\BaseService;
use App\Models\User;
use Spatie\QueryBuilder\AllowedFilter;

class UserService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = User::class;

    protected bool $cachingEnabled = true;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();

    }

    /**
     * Which fields are allowed to be filtered by.
     * @var array
     */
    protected function getAllowedFilters(): array
    {
        return [
            'name',
            AllowedFilter::exact('status'),
        ];
    }

    /**
     * Which fields are allowed to be sorted by.
     * @var array
     */
    protected function getAllowedSorts(): array
    {
        return [
            'created_at',
        ];
    }

    /**
     * Which relationships are allowed to be loaded.
     * @var array
     */
    protected function getAllowedIncludes(): array
    {
        return [
            'roles',
        ];
    }


    //update settings
    public function updateSettings(array $data)
    {
        $user = auth()->user();
        $currentSettings = $user->notification_settings ?? [];

        $newSettings = array_merge($currentSettings, $data);

        $user->notification_settings = $newSettings;
        $user->save();
        return $user->notification_settings;
    }


}
