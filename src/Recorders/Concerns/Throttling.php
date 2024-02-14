<?php

namespace Laravel\Pulse\Recorders\Concerns;

use Carbon\CarbonImmutable;
use DateInterval;
use Illuminate\Support\Facades\App;
use Laravel\Pulse\Events\IsolatedBeat;
use Laravel\Pulse\Events\SharedBeat;
use Laravel\Pulse\Support\CacheStoreResolver;

trait Throttling
{
    /**
     * Determine if the recorder is ready to record another snapshot.
     */
    protected function throttle(DateInterval|int $interval, SharedBeat|IsolatedBeat $event, callable $callback, ?string $key = null): void
    {
        if ($event instanceof SharedBeat) {
            $key = $event->instance.($key === null ? '' : ":{$key}");
        }

        $cache = App::make(CacheStoreResolver::class);

        $lastRunAt = $cache->store()->get($key);

        if ($lastRunAt !== null && CarbonImmutable::createFromTimestamp($lastRunAt)->addSeconds($this->secondsUntil($interval))->isFuture()) {
            return;
        }

        $callback($event);

        $cache->store()->put($key, $event->time->getTimestamp(), $interval);
    }
}
