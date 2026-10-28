<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    /**
     * System health check endpoint
     */
    public function index()
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => []
        ];

        // Database check
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = [
                'status' => 'healthy',
                'connection' => config('database.default')
            ];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['database'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }

        // Redis check
        try {
            Cache::store('redis')->put('health_check', 'ok', 10);
            $value = Cache::store('redis')->get('health_check');

            $health['checks']['redis'] = [
                'status' => $value === 'ok' ? 'healthy' : 'unhealthy',
                'driver' => config('cache.default')
            ];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['redis'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }

        // Queue check
        try {
            $queueSizes = [
                'messages' => $this->getQueueSize('messages'),
                'default' => $this->getQueueSize('default'),
                'campaigns' => $this->getQueueSize('campaigns'),
                'history' => $this->getQueueSize('history'),
            ];

            $totalJobs = array_sum($queueSizes);
            $queueStatus = $totalJobs > 10000 ? 'warning' : 'healthy';

            $health['checks']['queue'] = [
                'status' => $queueStatus,
                'sizes' => $queueSizes,
                'total_pending' => $totalJobs
            ];

            if ($queueStatus === 'warning') {
                $health['status'] = 'degraded';
            }
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['queue'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }

        // Failed jobs check
        try {
            $failedJobsCount = DB::table('failed_jobs')->count();

            $health['checks']['failed_jobs'] = [
                'status' => $failedJobsCount > 100 ? 'warning' : 'healthy',
                'count' => $failedJobsCount
            ];

            if ($failedJobsCount > 100 && $health['status'] === 'healthy') {
                $health['status'] = 'degraded';
            }
        } catch (\Exception $e) {
            $health['checks']['failed_jobs'] = [
                'status' => 'unknown',
                'error' => $e->getMessage()
            ];
        }

        // Cache hit rate (if available)
        try {
            $redis = Redis::connection('cache');
            $info = $redis->info('stats');

            if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                $hits = (int) $info['keyspace_hits'];
                $misses = (int) $info['keyspace_misses'];
                $total = $hits + $misses;
                $hitRate = $total > 0 ? round(($hits / $total) * 100, 2) : 0;

                $health['checks']['cache_performance'] = [
                    'hit_rate' => $hitRate . '%',
                    'hits' => $hits,
                    'misses' => $misses
                ];
            }
        } catch (\Exception $e) {
            // Cache performance check is optional
        }

        $statusCode = $health['status'] === 'healthy' ? 200 : ($health['status'] === 'degraded' ? 200 : 503);

        return response()->json($health, $statusCode);
    }

    /**
     * Get queue size for a specific queue
     */
    private function getQueueSize(string $queue): int
    {
        try {
            $redis = Redis::connection('queue');
            $prefix = config('database.redis.options.prefix');
            $key = $prefix . 'queues:' . $queue;

            return (int) $redis->llen($key);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Queue statistics endpoint
     */
    public function queueStats()
    {
        $stats = [];

        $queues = ['messages', 'default', 'campaigns', 'history'];

        foreach ($queues as $queue) {
            $stats[$queue] = [
                'pending' => $this->getQueueSize($queue),
                'failed' => DB::table('failed_jobs')
                    ->where('queue', $queue)
                    ->count(),
            ];
        }

        $stats['total_failed'] = DB::table('failed_jobs')->count();
        $stats['oldest_failed'] = DB::table('failed_jobs')
            ->orderBy('failed_at', 'asc')
            ->first();

        return response()->json($stats);
    }
}

