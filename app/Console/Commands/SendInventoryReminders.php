<?php

namespace App\Console\Commands;

use App\Models\UserInventory;
use App\Notifications\RestockReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendInventoryReminders extends Command
{
    protected $signature = 'inventory:send-reminders';
    protected $description = 'Send restock reminders to users based on their schedule';

    public function handle()
    {
        $this->info('Checking for inventory reminders...');

        $items = UserInventory::whereNotNull('next_reminder_at')
            ->whereDate('next_reminder_at', '<=', Carbon::now())
            ->with('user')
            ->get();

        foreach ($items as $item) {
            if ($item->user) {

                $item->user->notify(new RestockReminder($item));

                if ($item->reminder_frequency_days) {
                    $item->update([
                        'next_reminder_at' => Carbon::now()->addDays($item->reminder_frequency_days)
                    ]);
                } else {
                    $item->update(['next_reminder_at' => null]);
                }

                $this->info("Reminder sent to {$item->user->email} for {$item->item_name}");
            }
        }

        $this->info('All reminders processed.');
    }
}
