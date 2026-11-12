<?php

namespace App\Modules\Products\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Redis cache for the product catalog.
 *
 * Every cache key contains the current "version" token. A product write replaces
 * the token, so all older entries stop being used at once. Their memory is freed
 * by the TTL. This is simpler and safer than finding and deleting keys one by one,
 * and a late or repeated invalidation can never serve stale data.
 *
 * The version lives in the cache (Redis), not in the database. So the version check
 * is always current, even while the MySQL replica is still catching up.
 */
class ProductCatalogCache
{
    private const VERSION_KEY = 'products:version';

    public function enabled(): bool
    {
        return (bool) config('performance.cache.enabled');
    }

    public function version(): string
    {
        return Cache::rememberForever(self::VERSION_KEY, fn () => Str::random(16));
    }

    /** Called after every product write (see ProductObserver). */
    public function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, Str::random(16));
    }

    /**
     * ETag for a response. It changes when the catalog version or the request changes.
     */
    public function etag(string $version, string $name, array $params): string
    {
        return sha1($version.'|'.$name.'|'.$this->normalize($params));
    }

    /**
     * Returns the cached payload, or runs $load and caches its result for the TTL.
     * If $load throws (for example a 404), nothing is cached.
     */
    public function remember(string $version, string $name, array $params, Closure $load): array
    {
        $key = "products:{$version}:{$name}:".sha1($this->normalize($params));

        return Cache::remember($key, (int) config('performance.cache.products_ttl'), $load);
    }

    private function normalize(array $params): string
    {
        ksort($params);

        return json_encode($params, JSON_THROW_ON_ERROR);
    }
}
