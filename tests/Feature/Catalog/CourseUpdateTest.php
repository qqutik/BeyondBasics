<?php

declare(strict_types=1);

use App\Modules\Catalog\DTO\Course\CourseUpdateDTO;
use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\LanguageEnum;
use App\Modules\Catalog\Http\Requests\Course\CourseUpdateRequest;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Services\CourseService;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\travel;

function courseFixture(): Course
{
    $category = Category::query()->create(['name' => 'PHP', 'slug' => 'php', 'position' => 1, 'is_active' => true]);

    return Course::query()->create([
        'user_id' => User::factory()->create()->id,
        'category_id' => $category->id,
        'slug' => 'laravel-from-zero',
        'title' => 'Laravel from zero',
        'subtitle' => 'Build an API',
        'description' => 'Long description',
        'level' => CourseLevelEnum::BEGINNER,
        'language' => LanguageEnum::EN,
        'price' => 1999,
        'currency' => CurrencyEnum::USD,
    ]);
}

/**
 * Same path as the controller: validate with the request rules, build the DTO from validated data.
 *
 * @param  array<array-key, mixed>  $input  raw request input, validated inside
 */
function updateCourse(Course $course, array $input): Course
{
    /** @var array<string, mixed> $validated */
    $validated = Validator::make($input, (new CourseUpdateRequest)->rules())->validate();

    return app(CourseService::class)->update(CourseUpdateDTO::fromArray($validated), $course)->refresh();
}

it('updates only the fields that were sent', function () {
    $course = courseFixture();

    $updated = updateCourse($course, ['price' => 2999]);

    expect($updated->price)->toBe(2999)
        ->and($updated->title)->toBe('Laravel from zero')
        ->and($updated->subtitle)->toBe('Build an API')
        ->and($updated->category_id)->toBe($course->category_id)
        ->and($updated->level)->toBe(CourseLevelEnum::BEGINNER)
        ->and($updated->currency)->toBe(CurrencyEnum::USD);
});

it('casts enum fields from request strings', function () {
    $updated = updateCourse(courseFixture(), ['level' => 'advanced', 'language' => 'ua', 'currency' => 'EUR']);

    expect($updated->level)->toBe(CourseLevelEnum::ADVANCED)
        ->and($updated->language)->toBe(LanguageEnum::UA)
        ->and($updated->currency)->toBe(CurrencyEnum::EUR);
});

it('clears nullable fields when null is sent explicitly', function () {
    $updated = updateCourse(courseFixture(), ['subtitle' => null, 'category_id' => null, 'level' => null]);

    expect($updated->subtitle)->toBeNull()
        ->and($updated->category_id)->toBeNull()
        ->and($updated->level)->toBeNull()
        ->and($updated->title)->toBe('Laravel from zero');
});

it('does not let an update change the author, status or slug', function () {
    $course = courseFixture();
    $authorId = $course->user_id;

    $updated = updateCourse($course, [
        'title' => 'Laravel from scratch',
        'user_id' => User::factory()->create()->id,
        'status' => 'published',
        'slug' => 'hijacked',
    ]);

    expect($updated->title)->toBe('Laravel from scratch')
        ->and($updated->user_id)->toBe($authorId)
        ->and($updated->status)->toBe(CourseStatusEnum::DRAFT)
        ->and($updated->slug)->toBe('laravel-from-zero');
});

it('does nothing when no updatable field is sent', function () {
    $course = courseFixture();
    $before = $course->updated_at;

    travel(1)->minutes();
    $updated = updateCourse($course, []);

    expect($updated->updated_at->equalTo($before))->toBeTrue();
});

it('rejects invalid values', function (array $input, string $field) {
    $course = courseFixture();

    try {
        updateCourse($course, $input);
        $errors = [];
    } catch (ValidationException $e) {
        $errors = $e->errors();
    }

    expect($errors)->toHaveKey($field);
})->with([
    'null title' => [['title' => null], 'title'],
    'negative price' => [['price' => -1], 'price'],
    'unknown currency' => [['currency' => 'GBP'], 'currency'],
    'unknown level' => [['level' => 'expert'], 'level'],
    'missing category' => [['category_id' => 999999], 'category_id'],
]);
