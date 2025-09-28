<?php

return [
    'mysql' => [
        'session_variables' => [
            'sql_mode' => env('DB_SESSION_SQL_MODE', 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'),
            'innodb_strict_mode' => env('DB_INNODB_STRICT_MODE', 'ON'),
            'innodb_lock_wait_timeout' => env('DB_INNODB_LOCK_WAIT_TIMEOUT', 50),
            'innodb_flush_log_at_trx_commit' => env('DB_INNODB_FLUSH_LOG_AT_TRX_COMMIT', 1),
            'innodb_stats_persistent' => env('DB_INNODB_STATS_PERSISTENT', 'ON'),
            'optimizer_switch' => [
                'index_merge_intersection' => env('DB_OPTIMIZER_SWITCH_INDEX_MERGE_INTERSECTION', 'off'),
                'index_merge_union' => env('DB_OPTIMIZER_SWITCH_INDEX_MERGE_UNION', 'off'),
                'index_merge_sort_union' => env('DB_OPTIMIZER_SWITCH_INDEX_MERGE_SORT_UNION', 'off'),
            ],
            'max_execution_time' => env('DB_MAX_EXECUTION_TIME', 2000),
        ],
    ],
    'keyset' => [
        'default_per_page' => env('KEYSET_PAGINATION_PER_PAGE', 20),
        'max_per_page' => env('KEYSET_PAGINATION_MAX_PER_PAGE', 100),
        'page_parameter' => env('KEYSET_PAGINATION_PAGE_PARAMETER', 'per_page'),
        'cursor_name' => env('KEYSET_PAGINATION_CURSOR_NAME', 'cursor'),
        'default_order_column' => env('KEYSET_PAGINATION_DEFAULT_COLUMN', 'id'),
        'default_order_direction' => env('KEYSET_PAGINATION_DEFAULT_DIRECTION', 'desc'),
    ],
];
