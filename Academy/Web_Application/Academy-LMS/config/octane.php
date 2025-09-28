<?php

use App\Support\Octane\Listeners\FlushModelState;
use App\Support\Octane\Listeners\FlushScopedInstances;
use App\Support\Octane\Listeners\ResetAuthState;
use App\Support\Octane\Listeners\ResetTranslatorState;
use Laravel\Octane\Contracts\OperationTerminated;
use Laravel\Octane\Events\RequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\TickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\WorkerStopping;
use Laravel\Octane\Listeners\CloseMonologHandlers;
use Laravel\Octane\Listeners\CollectGarbage;
use Laravel\Octane\Listeners\DisconnectFromDatabases;
use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
use Laravel\Octane\Listeners\EnsureUploadedFilesCanBeMoved;
use Laravel\Octane\Listeners\FlushOnce;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use Laravel\Octane\Listeners\FlushUploadedFiles;
use Laravel\Octane\Listeners\ReportException;
use Laravel\Octane\Listeners\StopWorkerIfNecessary;
use Laravel\Octane\Octane;

return [
    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value determines the default "server" that will be used by Octane
    | when starting, restarting, or stopping your server via the CLI. You
    | are free to change this to the supported server of your choosing.
    |
    | Supported: "roadrunner", "swoole", "frankenphp"
    |
    */

    'server' => env('OCTANE_SERVER', 'roadrunner'),

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | When this configuration value is set to "true", Octane will inform the
    | framework that all absolute links must be generated using the HTTPS
    | protocol. Otherwise your links may be generated using plain HTTP.
    |
    */

    'https' => env('OCTANE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Worker Count
    |--------------------------------------------------------------------------
    |
    | This option allows you to define the number of workers that should be
    | started when Octane boots. The "auto" setting intelligently chooses
    | the optimal number based on available CPU cores on the server.
    |
    */

    'workers' => env('OCTANE_WORKERS', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Max Requests
    |--------------------------------------------------------------------------
    |
    | Configure the maximum number of requests a worker should handle before
    | it is recycled. Recycling workers periodically helps mitigate leaks
    | and ensures configuration changes are respected over time.
    |
    */

    'max_requests' => env('OCTANE_MAX_REQUESTS', 250),

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | All of the event listeners for Octane's events are defined below. These
    | listeners are responsible for resetting your application's state for
    | the next request. You may add your own listeners to the list.
    |
    */

    'listeners' => [
        WorkerStarting::class => [
            EnsureUploadedFilesAreValid::class,
            EnsureUploadedFilesCanBeMoved::class,
        ],

        RequestReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            ...Octane::prepareApplicationForNextRequest(),
        ],

        RequestHandled::class => [
            ResetAuthState::class,
        ],

        RequestTerminated::class => [
            FlushUploadedFiles::class,
            FlushScopedInstances::class,
            ResetTranslatorState::class,
        ],

        TaskReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
        ],

        TaskTerminated::class => [
            FlushScopedInstances::class,
        ],

        TickReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
        ],

        TickTerminated::class => [
            FlushScopedInstances::class,
        ],

        OperationTerminated::class => [
            FlushOnce::class,
            FlushTemporaryContainerInstances::class,
            DisconnectFromDatabases::class,
            CollectGarbage::class,
            FlushModelState::class,
        ],

        WorkerErrorOccurred::class => [
            ReportException::class,
            StopWorkerIfNecessary::class,
        ],

        WorkerStopping::class => [
            CloseMonologHandlers::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Bindings
    |--------------------------------------------------------------------------
    |
    | The bindings listed below will either be pre-warmed when a worker boots
    | or they will be flushed before every new request. Flushing a binding
    | will force the container to resolve that binding again when asked.
    |
    */

    'warm' => [
        ...Octane::defaultServicesToWarm(),
        'auth',
        'cache',
        'config',
        'db',
    ],

    'flush' => [
        'auth',
        'db',
        'events',
        'translator',
        'view',
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Tables
    |--------------------------------------------------------------------------
    |
    | When using Swoole or FrankenPHP, you may define additional tables that
    | should be made available to workers. These tables allow you to share
    | state, counters, or cache data without hitting the database.
    |
    */

    'tables' => [
        'presence:2048' => [
            'community_id' => 'int',
            'members_online' => 'int',
            'refreshed_at' => 'int',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Cache Table
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may leverage the Octane cache, which is powered
    | by a Swoole table. You may set the maximum number of rows as well as
    | the number of bytes per row using the configuration options below.
    |
    */

    'cache' => [
        'rows' => env('OCTANE_CACHE_ROWS', 2048),
        'bytes' => env('OCTANE_CACHE_BYTES', 32768),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watching
    |--------------------------------------------------------------------------
    |
    | The following list of files and directories will be watched when using
    | the --watch option offered by Octane. If any of the directories and
    | files are changed, Octane will automatically reload your workers.
    |
    */

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        '.env',
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection Threshold
    |--------------------------------------------------------------------------
    |
    | When executing long-lived PHP scripts such as Octane, memory can build
    | up before being cleared by PHP. You can force Octane to run garbage
    | collection if your application consumes this amount of megabytes.
    |
    */

    'garbage' => env('OCTANE_GARBAGE_THRESHOLD', 64),

    /*
    |--------------------------------------------------------------------------
    | Maximum Execution Time
    |--------------------------------------------------------------------------
    |
    | Configure the maximum execution time for requests being handled by
    | Octane. Setting the value to 0 indicates that there isn't a specific
    | time limit on Octane request execution time.
    |
    */

    'max_execution_time' => env('OCTANE_MAX_EXECUTION_TIME', 30),

    /*
    |--------------------------------------------------------------------------
    | Leak Guard
    |--------------------------------------------------------------------------
    |
    | These settings drive leak-guard behaviour implemented by the custom
    | Octane listeners above. Adjust intervals and toggles to ensure
    | long-lived workers stay healthy under sustained load.
    |
    */

    'leak_guard' => [
        'enabled' => env('OCTANE_LEAK_GUARD', true),
        'auth_reset' => env('OCTANE_LEAK_GUARD_AUTH', true),
        'translator_reset' => env('OCTANE_LEAK_GUARD_TRANSLATOR', true),
        'model_flush' => env('OCTANE_LEAK_GUARD_MODEL', true),
    ],
];
