<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services\Catalog;

use App\Modules\Catalog\DTO\Course\CourseFilterDTO;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Repositories\CourseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Public catalog for students: courses on sale only.
 */
final readonly class CatalogService
{
    /**
     * Part of every list cache key. flush() bumps it, so old keys are never read again and expire by TTL
     * (works with any cache store: database / file have no tags).
     */
    private const string CACHE_VERSION_KEY = 'catalog:version';

    public function __construct(private CourseRepository $repository) {}

    /**
     * Cached per filters + page for catalog.cache.ttl seconds.
     *
     * @return LengthAwarePaginator<int, Course>
     */
    public function findAll(CourseFilterDTO $filter): LengthAwarePaginator
    {
        $key = sprintf(
            'catalog:v%d:list:%s:page:%d',
            $this->cacheVersion(),
            hash('xxh128', (string) json_encode($filter->toArray())),
            Paginator::resolveCurrentPage(),
        );

        return Cache::remember($key, Config::integer('catalog.cache.ttl'), fn (): LengthAwarePaginator => $this->repository->findPublished($filter));
    }

    public function findOneBySlug(string $slug): ?Course
    {
        return $this->repository->findPublishedBySlug($slug);
    }

    /**
     * Call when a published course changes: approved, archived, edited or deleted.
     */
    public function flush(): void
    {
        Cache::add(self::CACHE_VERSION_KEY, 1);
        Cache::increment(self::CACHE_VERSION_KEY);
    }

    private function cacheVersion(): int
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, 1);

        return is_numeric($version) ? (int) $version : 1;
    }
}
