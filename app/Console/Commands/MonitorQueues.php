<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MonitorQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:status 
                            {--watch : Watch queue status in real-time}
                            {--clear-failed : Clear all failed jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor queue sizes and status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('clear-failed')) {
            return $this->clearFailedJobs();
        }

        if ($this->option('watch')) {
            return $this->watchQueues();
        }

        return $this->displayStatus();
    }

    /**
     * Display current queue status
     */
    private function displayStatus()
    {
        $this->info('Queue Status - ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();

        $queues = ['messages', 'default', 'campaigns', 'history'];
        $data = [];

        foreach ($queues as $queue) {
            $pending = $this->getQueueSize($queue);
            $failed = DB::table('failed_jobs')->where('queue', $queue)->count();

            $data[] = [
                'Queue' => $queue,
                'Pending' => $pending,
                'Failed' => $failed,
                'Status' => $this->getQueueStatus($pending),
            ];
        }

        $this->table(['Queue', 'Pending', 'Failed', 'Status'], $data);

        // Total failed jobs
        $totalFailed = DB::table('failed_jobs')->count();
        $this->newLine();
        $this->info("Total Failed Jobs: {$totalFailed}");

        if ($totalFailed > 0) {
            $this->warn("Run 'php artisan queue:failed' to view failed jobs");
            $this->warn("Run 'php artisan queue:retry all' to retry all failed jobs");
        }
        Cache::set('queue-monitor-last-run', now());
        return 0;
    }

    /**
     * Watch queues in real-time
     */
    private function watchQueues()
    {
        $this->info('Watching queues... (Press Ctrl+C to stop)');

        while (true) {
            // Clear screen
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                system('cls');
            } else {
                system('clear');
            }

            $this->displayStatus();
            sleep(2); // Update every 2 seconds
        }
    }

    /**
     * Clear all failed jobs
     */
    private function clearFailedJobs()
    {
        $count = DB::table('failed_jobs')->count();

        if ($count === 0) {
            $this->info('No failed jobs to clear.');
            return 0;
        }

        if ($this->confirm("Are you sure you want to delete {$count} failed jobs?")) {
            DB::table('failed_jobs')->truncate();
            $this->info("Cleared {$count} failed jobs.");
        } else {
            $this->info('Operation cancelled.');
        }

        return 0;
    }

    /**
     * Get queue size
     */
    private function getQueueSize(string $queue): int
    {
        try {
            $redis = Redis::connection('queue');
            $prefix = config('database.redis.options.prefix');

            // Laravel uses format: prefix + 'queues:' + queue_name
            $key = $prefix . 'queues:' . $queue;

            $size = (int) $redis->llen($key);

            // Debug: log the key being checked
            if ($this->option('verbose')) {
                $this->line("Checking key: {$key}, Size: {$size}");
            }

            return $size;
        } catch (\Exception $e) {
            $this->error("Error getting queue size for '{$queue}': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get queue status indicator
     */
    private function getQueueStatus(int $size): string
    {
        if ($size === 0) {
            return '✓ Empty';
        } elseif ($size < 100) {
            return '✓ Normal';
        } elseif ($size < 1000) {
            return '⚠ Busy';
        } else {
            return '✗ Overloaded';
        }
    }
}

