<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catalog cache (Redis) and HTTP revalidation (ETag / 304)
    |--------------------------------------------------------------------------
    |
    | When disabled, product requests always read the database and keep the
    | default "Cache-Control: no-store" header, which is the baseline for the
    | load tests.
    |
    */
    'cache' => [
        'enabled' => (bool) env('PERF_CACHE_ENABLED', true),

        // Time to live of every cached product response. It is a safety net:
        // writes also invalidate the cache immediately (see ProductCatalogCache).
        'products_ttl' => (int) env('PERF_PRODUCTS_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Read replica
    |--------------------------------------------------------------------------
    |
    | When enabled, product and order reads use the "mysql_replica" connection,
    | but only while the replica has applied every transaction the primary has
    | committed so far (see ReadRouter). Writes always go to the primary.
    |
    */
    'replica' => [
        'enabled' => (bool) env('PERF_REPLICA_ENABLED', false),
    ],

];
