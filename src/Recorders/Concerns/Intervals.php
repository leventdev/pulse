<?php

namespace Laravel\Pulse\Recorders\Concerns;

use DateInterval;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Pulse\Events\IsolatedBeat;
use Laravel\Pulse\Events\SharedBeat;

trait Intervals
{
    /**
     * Determine if the recorder is ready to record another snapshot.
     */
    protected function throttle(DateInterval|int $interval, SharedBeat|IsolatedBeat $event, callable $callback, ?string $key = null): void
    {
        if ($event instanceof SharedBeat) {
            $key = $event->key.($key === null ? '' : ":{$key}");
        }

        RateLimiter::attempt($key, 1, fn () => $callback($event), $this->secondsUntil($interval));
    }
}
