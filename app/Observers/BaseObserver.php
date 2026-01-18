<?php

namespace App\Observers;

use App\Services\BaseService;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BaseObserver
{

    protected array $relatedCacheTags = [];


    public function created(Model $model): void
    {
        Log::info('BaseObserver created called for model: ' . get_class($model) . ' with ID: ' . $model->getKey());
        $this->flushCollectionCache($model);
    }


    public function updated(Model $model): void
    {
        Log::info('BaseObserver updated called for model: ' . get_class($model) . ' with ID: ' . $model->getKey());
        $service = $this->getServiceForModel($model);
        if ($service) {
            $service->clearItemCache($model);
        }
        $this->flushCollectionCache($model);
    }

    public function deleted(Model $model): void
    {
        Log::info('BaseObserver deleted called for model: ' . get_class($model) . ' with ID: ' . $model->getKey());
        $service = $this->getServiceForModel($model);
        if ($service) {
            $service->clearItemCache($model);
        }
        $this->flushCollectionCache($model);
    }


    protected function flushCollectionCache(Model $model): void
    {

        Cache::tags($model->getTable())->flush();


        if (!empty($this->relatedCacheTags)) {
            Cache::tags($this->relatedCacheTags)->flush();
        }
    }

    /**
     * @return BaseService|null
     */
    protected function getServiceForModel(Model $model): ?BaseService
    {
        $modelClass = get_class($model);

        $serviceClass = str_replace('\\Models\\', '\\Services\\', $modelClass) . 'Service';

        if (class_exists($serviceClass)) {

            return app($serviceClass);
        }

        return null;
    }
}
