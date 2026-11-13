<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Course;
use App\Modules\Identity\Enums\Role;

use function Pest\Laravel\getJson;

/**
 * Three own courses of one author with different fields, plus that author's headers.
 *
 * @return array{headers: array{Authorization: string}, php: Category, design: Category}
 */
function filterFixture(): array
{
    $author = userWithRoles(Role::AUTHOR);
    $php = Category::query()->create(['name' => 'PHP', 'slug' => 'php-filter', 'position' => 1, 'is_active' => true]);
    $design = Category::query()->create(['name' => 'Design', 'slug' => 'design-filter', 'position' => 2, 'is_active' => true]);

    $make = function (string $slug, Category $category, CourseLevelEnum $level, LanguageEnum $language, int $price, CourseStatusEnum $status, CurrencyEnum $currency = CurrencyEnum::USD) use ($author): void {
        $course = Course::query()->create([
            'user_id' => $author->id, 'category_id' => $category->id, 'slug' => $slug, 'title' => ucfirst($slug),
            'level' => $level, 'language' => $language, 'price' => $price, 'currency' => $currency,
        ]);
        $course->status = $status;
        $course->save();
    };

    $make('laravel', $php, CourseLevelEnum::BEGINNER, LanguageEnum::EN, 1999, CourseStatusEnum::DRAFT);
    $make('symfony', $php, CourseLevelEnum::ADVANCED, LanguageEnum::UA, 4999, CourseStatusEnum::REVIEW, CurrencyEnum::UAH);
    $make('figma', $design, CourseLevelEnum::BEGINNER, LanguageEnum::EN, 0, CourseStatusEnum::PUBLISHED);

    return ['headers' => bearer(apiLogin($author->email)['access_token']), 'php' => $php, 'design' => $design];
}

/**
 * @param  array{Authorization: string}  $headers
 * @return list<string> lowercased titles of the returned courses (title = ucfirst(slug)), sorted
 */
function slugsFor(string $query, array $headers): array
{
    /** @var list<string> $slugs */
    $slugs = getJson("/api/course?{$query}", $headers)->assertOk()->json('data.*.title') ?? [];
    $slugs = array_map(strtolower(...), $slugs);
    sort($slugs);

    return $slugs;
}

it('filters by category, status, level and language', function () {
    ['headers' => $h, 'php' => $php] = filterFixture();

    expect(slugsFor("category_id={$php->id}", $h))->toBe(['laravel', 'symfony'])
        ->and(slugsFor('status=review', $h))->toBe(['symfony'])
        ->and(slugsFor('level=beginner', $h))->toBe(['figma', 'laravel'])
        ->and(slugsFor('language=ua', $h))->toBe(['symfony']);
});

it('filters by a price range in minor units', function () {
    ['headers' => $h] = filterFixture();

    expect(slugsFor('price_max=0', $h))->toBe(['figma'])
        ->and(slugsFor('price_min=1000&price_max=2000', $h))->toBe(['laravel'])
        ->and(slugsFor('price_min=2000', $h))->toBe(['symfony']);
});

it('filters by currency, alone and with a price range', function () {
    ['headers' => $h] = filterFixture();

    expect(slugsFor('currency=UAH', $h))->toBe(['symfony'])
        ->and(slugsFor('currency=USD&price_max=2000', $h))->toBe(['figma', 'laravel'])
        ->and(slugsFor('currency=EUR', $h))->toBe([]);
});

it('combines filters', function () {
    ['headers' => $h, 'php' => $php] = filterFixture();

    expect(slugsFor("category_id={$php->id}&level=beginner&price_max=2000", $h))->toBe(['laravel']);
});

it('rejects invalid filter values', function () {
    ['headers' => $h] = filterFixture();

    getJson('/api/course?level=expert&status=gone&currency=GBP&price_min=5000&price_max=100', $h)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['level', 'status', 'currency', 'price_max']);
});
