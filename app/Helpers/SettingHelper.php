<?php
namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SettingHelper
{
    public static function get($key, $default = null)
    {
        return Cache::tags('settings')->remember("setting_{$key}", 60 * 60, function () use ($key, $default) {
            $value = DB::table('settings')->where('key', $key)->value('value');
            return $value ?? $default;
        });
    }
}
