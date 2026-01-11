<?php

namespace App\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    /**
     * Caches the result of a closure using a dynamically generated key and tag.
     * This method is designed to be called from within a service class.
     *
     * @param string $method     The name of the method being called (e.g., __FUNCTION__).
     * @param array  $arguments  The arguments passed to the method (e.g., func_get_args()).
     * @param Closure $callback  The closure to execute and cache if not found in cache.
     * @param int    $ttl        Number of seconds to cache the result (default: 1 hour).
     * @return mixed
     */
    protected function cache(string $method, array $arguments, Closure $callback, int $ttl = 3600, bool $perUser = false)
    {
        //Check if caching is enabled for the service.
        // The `$cachingEnabled` property will be defined in BaseService.
        if (property_exists($this, 'cachingEnabled') && $this->cachingEnabled === false) {
            return $callback();
        }
        // Uses the model's table name as the cache tag.
        // Assumes this trait is used in a class with a 'model' property.
        if (!property_exists($this, 'model')) {
            // If there is no model property, cache without tags.
            return $callback();
        }

        $tag = $this->model->getTable();
        $key = $this->generateCacheKey($method, $arguments, $perUser);

        return Cache::tags($tag)->remember($key, $ttl, $callback);
    }

    /**
     * Clears the cache for a single item (e.g., getById).
     * This method is intended to be called from an observer.
     *
     * @param Model $modelInstance The model instance whose cache needs to be cleared.
     */
    public function clearItemCache(Model $modelInstance): void
    {
        if (!property_exists($this, 'model')) {
            return;
        }

        $tag = $modelInstance->getTable();
        $key = $this->generateCacheKey('getById', [$modelInstance->getKey()], $this->cachePerUser ?? false);
        Cache::tags($tag)->forget($key);
    }

    /**
     * Generates a unique cache key based on the class, method, and arguments.
     *
     * @param string $method
     * @param array $arguments
     * @return string
     */
    private function generateCacheKey(string $method, array $arguments, bool $perUser): string
    {

        $keyParts = [
            get_class($this),
            $method,
        ];

        if ($perUser) {
            $keyParts[] = 'user_' . (auth()->id() ?? 'guest');
        }

        $queryString = '';
        if ($method === 'getAll') {
            $queryParams = request()->query();
            ksort($queryParams);
            // $queryString = json_encode($queryParams);
            $queryString = http_build_query($queryParams);
        }

        // ksort($arguments);
        $argumentString = json_encode($arguments);

        $keyParts[] = md5($argumentString . $queryString);

        return implode(':', $keyParts);
    }
}
