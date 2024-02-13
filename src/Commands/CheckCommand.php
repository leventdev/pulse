<?php

namespace Laravel\Pulse\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Laravel\Pulse\Events\IsolatedBeat;
use Laravel\Pulse\Events\SharedBeat;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Support\CacheStoreResolver;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @internal
 */
#[AsCommand(name: 'pulse:check')]
class CheckCommand extends Command
{
    /**
     * The command's signature.
     *
     * @var string
     */
    public $signature = 'pulse:check';

    /**
     * The command's description.
     *
     * @var string
     */
    public $description = 'Take a snapshot of the current server\'s pulse';

    /**
     * Handle the command.
     */
    public function handle(
        Pulse $pulse,
        CacheStoreResolver $cache,
        Dispatcher $event,
    ): int {
        $lastRestart = $cache->store()->get('laravel:pulse:restart');

        $lock = ($store = $cache->store()->getStore()) instanceof LockProvider
            ? $store->lock('laravel:pulse:check', 5)
            : null;

        $key = Str::random();

        while (true) {
            $now = CarbonImmutable::now();

            if ($lastRestart !== $cache->store()->get('laravel:pulse:restart')) {
                return self::SUCCESS;
            }

            if ($lock?->get()) {
                $event->dispatch(new IsolatedBeat($now));
            }

            $event->dispatch(new SharedBeat($now, $key));

            $pulse->ingest();

            Sleep::for(1)->second();
        }
    }
}
