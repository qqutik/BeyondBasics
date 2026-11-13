<?php

declare(strict_types=1);

use App\Modules\Catalog\DTO\Course\CourseUpdateDTO;
use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\CourseVersion;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Services\Catalog\CatalogService;
use App\Modules\Catalog\Services\CourseService;
use App\Modules\Catalog\Services\CourseStatusService;
use App\Modules\Identity\Models\User;

use function Pest\Laravel\getJson;

/**
 * A course in the given status with a small curriculum (1 section, 2 lessons); published ones get a version.
 *
 * @param  array<string, mixed>  $attributes
 */
function catalogCourse(string $slug, CourseStatusEnum $status = CourseStatusEnum::PUBLISHED, array $attributes = []): Course
{
    $course = Course::query()->create([
        'user_id' => User::factory()->create()->id,
        'slug' => $slug,
        'title' => ucfirst($slug),
        'description' => "About {$slug}",
        'level' => CourseLevelEnum::BEGINNER,
        'language' => LanguageEnum::EN,
        'price' => 1000,
        'currency' => CurrencyEnum::USD,
        ...$attributes,
    ]);

    $section = Section::query()->create(['course_id' => $course->id, 'title' => 'Intro', 'position' => 1]);

    foreach ([
        ['type' => LessonTypeEnum::VIDEO, 'title' => 'Welcome', 'body' => 'SECRET BODY', 'media_id' => 55, 'duration_sec' => 120, 'is_preview' => true],
        ['type' => LessonTypeEnum::TEXT, 'title' => 'Setup', 'body' => 'SECRET TEXT', 'duration_sec' => 300],
    ] as $index => $data) {
        $lesson = new Lesson($data);
        $lesson->section_id = $section->id;
        $lesson->position = $index + 1;
        $lesson->save();
    }

    if ($status === CourseStatusEnum::PUBLISHED) {
        $version = new CourseVersion(['course_id' => $course->id, 'published_at' => now(), 'snapshot' => []]);
        $version->version = 1;
        $version->save();

        $course->published_version_id = $version->id;
        $course->published_at = now();
    }

    $course->status = $status;
    $course->save();

    return $course;
}

/**
 * @return list<string>
 */
function publicSlugs(string $query = ''): array
{
    /** @var list<string> $slugs */
    $slugs = getJson("/api/courses?{$query}")->assertOk()->json('data.*.slug') ?? [];
    sort($slugs);

    return $slugs;
}

it('lists only published courses without login', function () {
    catalogCourse('live');
    catalogCourse('draft', CourseStatusEnum::DRAFT);
    catalogCourse('review', CourseStatusEnum::REVIEW);
    catalogCourse('archived', CourseStatusEnum::ARCHIVED);
    catalogCourse('deleted')->delete();

    expect(publicSlugs())->toBe(['live']);

    getJson('/api/courses')
        ->assertJsonPath('data.0.price.formatted', '$10.00')
        ->assertJsonMissingPath('data.0.id')
        ->assertJsonMissingPath('data.0.status');
});

it('ignores user_id and status from the client', function () {
    $draft = catalogCourse('hidden-draft', CourseStatusEnum::DRAFT);
    catalogCourse('visible');

    expect(publicSlugs("status=draft&user_id={$draft->user_id}"))->toBe(['visible']);
});

it('uses the same filters as the editor list', function () {
    $php = Category::query()->create(['name' => 'PHP', 'slug' => 'php-public', 'position' => 1, 'is_active' => true]);
    catalogCourse('laravel', attributes: ['category_id' => $php->id, 'price' => 1999]);
    catalogCourse('figma', attributes: ['price' => 0, 'level' => CourseLevelEnum::ADVANCED]);
    catalogCourse('uah', attributes: ['currency' => CurrencyEnum::UAH, 'language' => LanguageEnum::UA]);

    expect(publicSlugs("category_id={$php->id}"))->toBe(['laravel'])
        ->and(publicSlugs('level=advanced'))->toBe(['figma'])
        ->and(publicSlugs('language=ua'))->toBe(['uah'])
        ->and(publicSlugs('currency=USD&price_max=0'))->toBe(['figma'])
        ->and(publicSlugs('title=lara'))->toBe(['laravel']);
});

it('shows a course page with the curriculum but without lesson content', function () {
    catalogCourse('laravel');

    $response = getJson('/api/courses/laravel')
        ->assertOk()
        ->assertJsonPath('data.slug', 'laravel')
        ->assertJsonPath('data.description', 'About laravel')
        ->assertJsonPath('data.sections.0.title', 'Intro')
        ->assertJsonPath('data.sections.0.lessons.0.title', 'Welcome')
        ->assertJsonPath('data.sections.0.lessons.0.is_preview', true)
        ->assertJsonPath('data.sections.0.lessons.1.duration_sec', 300);

    expect($response->getContent())->not->toContain('SECRET')
        ->and($response->json('data.sections.0.lessons.0'))->not->toHaveKeys(['body', 'media_id']);
});

it('shows curriculum changes of a published course right away', function () {
    $course = catalogCourse('live-edit');
    $section = $course->sections()->firstOrFail();
    $lesson = new Lesson(['type' => LessonTypeEnum::TEXT, 'title' => 'Brand new', 'body' => 'x']);
    $lesson->section_id = $section->id;
    $lesson->position = 3;
    $lesson->save();

    getJson('/api/courses/live-edit')->assertOk()->assertJsonPath('data.sections.0.lessons.2.title', 'Brand new');
});

it('returns 404 for a course that is not published', function () {
    catalogCourse('draft-page', CourseStatusEnum::DRAFT);
    catalogCourse('archived-page', CourseStatusEnum::ARCHIVED);

    getJson('/api/courses/draft-page')->assertNotFound();
    getJson('/api/courses/archived-page')->assertNotFound();
    getJson('/api/courses/missing')->assertNotFound();
});

describe('cache', function () {
    it('serves the list from cache until it is flushed', function () {
        $course = catalogCourse('cached');
        expect(publicSlugs())->toBe(['cached']);

        // changed behind the services' back: still the cached answer
        $course->status = CourseStatusEnum::ARCHIVED;
        $course->save();
        expect(publicSlugs())->toBe(['cached']);

        app(CatalogService::class)->flush();
        expect(publicSlugs())->toBe([]);
    });

    it('keeps pages and filters apart', function () {
        catalogCourse('first', attributes: ['level' => CourseLevelEnum::ADVANCED]);
        catalogCourse('second');

        expect(publicSlugs('level=advanced'))->toBe(['first'])
            ->and(publicSlugs())->toBe(['first', 'second'])
            ->and(getJson('/api/courses?per_page=1&page=2')->json('data'))->toHaveCount(1);
    });

    it('is flushed when a course is archived, edited or deleted', function () {
        $archived = catalogCourse('to-archive');
        $edited = catalogCourse('to-edit');
        $deleted = catalogCourse('to-delete');
        $courses = app(CourseService::class);

        expect(publicSlugs())->toBe(['to-archive', 'to-delete', 'to-edit']);

        app(CourseStatusService::class)->archive($archived);
        expect(publicSlugs())->toBe(['to-delete', 'to-edit']);

        $courses->update(CourseUpdateDTO::fromArray(['title' => 'Renamed']), $edited);
        expect(getJson('/api/courses?title=Renamed')->json('data.*.slug'))->toBe(['to-edit']);

        $courses->destroy($deleted);
        expect(publicSlugs())->toBe(['to-edit']);
    });
});
