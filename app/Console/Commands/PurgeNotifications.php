<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Notification;

class PurgeNotifications extends Command {

    protected $signature = 'notifications:purge';
    protected $description = 'Purge old notifications';

    public function handle() {
        $cutoffDate = Carbon::now()->subDays(15);
        Notification::where('created_at', '<', $cutoffDate)->delete();
        $this->info('Old notifications purged successfully.');
    }

}
