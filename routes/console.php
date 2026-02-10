<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


Schedule::command('videos:cleanup')->daily();

Schedule::command('inventory:send-reminders')->daily('08:00');

Schedule::command('appointment:send-reminders')->everyMinute();
