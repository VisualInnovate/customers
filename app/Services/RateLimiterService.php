<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimiterService
{
    /**
     * Check if a rate limit is exceeded
     *
     * @param string $key Unique identifier for this rate limit
     * @param int $maxAttempts Maximum number of attempts allowed
     * @param int $decaySeconds Time window in seconds
     * @return bool True if limit is exceeded, false otherwise
     */
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            Log::warning("Rate limit exceeded for key: {$key}");
            return true;
        }

        return false;
    }

    /**
     * Increment the counter for a given key
     *
     * @param string $key
     * @param int $decaySeconds
     * @return int The new count
     */
    public static function hit(string $key, int $decaySeconds): int
    {
        $attempts = Cache::get($key, 0);
        $newAttempts = $attempts + 1;

        Cache::put($key, $newAttempts, $decaySeconds);

        return $newAttempts;
    }

    /**
     * Get remaining attempts
     *
     * @param string $key
     * @param int $maxAttempts
     * @return int
     */
    public static function remaining(string $key, int $maxAttempts): int
    {
        $attempts = Cache::get($key, 0);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get seconds until rate limit resets
     *
     * @param string $key
     * @return int
     */
    public static function availableIn(string $key): int
    {
        $ttl = Cache::getStore()->getRedis()->ttl(
            config('cache.prefix') . $key
        );

        return max(0, $ttl);
    }

    /**
     * Clear the rate limiter for a given key
     *
     * @param string $key
     * @return void
     */
    public static function clear(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Throttle WhatsApp API requests to comply with Meta rate limits
     * Meta allows ~80 messages per second per phone number
     *
     * @param string $phoneNumberId
     * @return bool True if request can proceed, false if should wait
     */
    public static function throttleWhatsAppAPI(string $phoneNumberId): bool
    {
        $key = "whatsapp_api_rate_{$phoneNumberId}";

        // 80 messages per second = 4800 per minute
        if (self::tooManyAttempts($key, 4500, 60)) {
            $waitTime = self::availableIn($key);
            Log::info("WhatsApp API rate limit reached for {$phoneNumberId}, waiting {$waitTime} seconds");
            return false;
        }

        self::hit($key, 60);
        return true;
    }

    /**
     * Wait and retry logic for WhatsApp API calls
     *
     * @param string $phoneNumberId
     * @param callable $callback
     * @param int $maxRetries
     * @return mixed
     */
    public static function executeWithRateLimit(string $phoneNumberId, callable $callback, int $maxRetries = 3)
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            if (self::throttleWhatsAppAPI($phoneNumberId)) {
                return $callback();
            }

            $attempts++;
            $waitTime = min(self::availableIn("whatsapp_api_rate_{$phoneNumberId}"), 5);

            if ($attempts < $maxRetries) {
                Log::info("Waiting {$waitTime} seconds before retry (attempt {$attempts}/{$maxRetries})");
                sleep($waitTime);
            }
        }

        throw new \Exception("Rate limit exceeded after {$maxRetries} attempts");
    }
}

