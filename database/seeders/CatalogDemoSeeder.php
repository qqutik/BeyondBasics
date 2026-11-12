<?php

declare(strict_types=1);

namespace Database\Seeders;

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
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo catalog for local development: categories, courses in different statuses,
 * sections, lessons and a published snapshot (course_versions) for every published course.
 *
 * Additive and idempotent: existing rows are left alone, a course whose slug already exists is skipped,
 * so it can be run again safely:  php84 artisan db:seed --class=CatalogDemoSeeder
 *
 * Lives in database/seeders (not in a module) because it touches two modules: Identity (the author) and Catalog.
 * media_id stays NULL: the Media module does not exist yet, a fake id would break its future FK.
 */
final class CatalogDemoSeeder extends Seeder
{
    private const string AUTHOR_EMAIL = 'author@beyondbasics.test';

    public function run(): void
    {
        DB::transaction(function (): void {
            $authorId = $this->author();
            $categories = $this->categories();

            foreach ($this->courses() as $data) {
                $this->course($authorId, $categories, $data);
            }
        });
    }

    /**
     * Author account (password from UserFactory) with the author role.
     */
    private function author(): int
    {
        $author = User::query()->where('email', self::AUTHOR_EMAIL)->first()
            ?? User::factory()->create(['name' => 'Demo Author', 'email' => self::AUTHOR_EMAIL]);

        DB::table('user_roles')->insertOrIgnore([
            ['user_id' => $author->id, 'role' => Role::STUDENT->value, 'granted_by' => null, 'granted_at' => now()],
            ['user_id' => $author->id, 'role' => Role::AUTHOR->value, 'granted_by' => null, 'granted_at' => now()],
        ]);

        return $author->id;
    }

    /**
     * @return array<string, int> slug => id
     */
    private function categories(): array
    {
        $tree = [
            ['development', 'Development', [
                ['backend', 'Backend', [['php-laravel', 'PHP & Laravel'], ['databases', 'Databases']]],
                ['frontend', 'Frontend', [['javascript', 'JavaScript']]],
                ['devops', 'DevOps', []],
            ]],
            ['design', 'Design', [['ui-ux', 'UI / UX', []]]],
            ['business', 'Business', [['product-management', 'Product management', []]]],
        ];

        $ids = [];
        $this->categoryLevel($tree, null, $ids);

        return $ids;
    }

    /**
     * @param  list<array{0: string, 1: string, 2?: list<mixed>}>  $nodes  [slug, name, children]
     * @param  array<string, int>  $ids
     */
    private function categoryLevel(array $nodes, ?int $parentId, array &$ids): void
    {
        foreach ($nodes as $index => $node) {
            $category = Category::query()->firstOrCreate(
                ['parent_id' => $parentId, 'slug' => $node[0]],
                ['name' => $node[1], 'position' => $index + 1, 'is_active' => true],
            );

            $ids[$node[0]] = $category->id;

            /** @var list<array{0: string, 1: string, 2?: list<mixed>}> $children */
            $children = $node[2] ?? [];
            $this->categoryLevel($children, $category->id, $ids);
        }
    }

    /**
     * @param  array<string, int>  $categories
     * @param  array<string, mixed>  $data
     */
    private function course(int $authorId, array $categories, array $data): void
    {
        /** @var string $slug */
        $slug = $data['slug'];

        if (Course::withTrashed()->where('slug', $slug)->exists()) {
            return;
        }

        /** @var string $category */
        $category = $data['category'];
        /** @var CourseStatusEnum $status */
        $status = $data['status'];
        /** @var list<array{title: string, lessons: list<array<string, mixed>>}> $sections */
        $sections = $data['sections'];

        $course = Course::query()->create([
            'user_id' => $authorId,
            'category_id' => $categories[$category],
            'slug' => $slug,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'description' => $data['description'],
            'level' => $data['level'],
            'language' => $data['language'],
            'price' => $data['price'],
            'currency' => $data['currency'],
        ]);

        foreach ($sections as $sectionIndex => $sectionData) {
            $section = new Section(['title' => $sectionData['title']]);
            $section->course_id = $course->id;
            $section->position = $sectionIndex + 1;
            $section->save();

            foreach ($sectionData['lessons'] as $lessonIndex => $lessonData) {
                $lesson = new Lesson($lessonData);
                $lesson->section_id = $section->id;
                $lesson->position = $lessonIndex + 1;
                $lesson->save();
            }
        }

        // status and publishing fields are not fillable: assigned explicitly, like the status workflow does
        $course->status = $status;

        if ($status === CourseStatusEnum::PUBLISHED || $status === CourseStatusEnum::ARCHIVED) {
            $publishedAt = now()->subDays(random_int(5, 60));

            $version = new CourseVersion([
                'course_id' => $course->id,
                'snapshot' => $this->snapshot($course),
                'published_at' => $publishedAt,
            ]);
            $version->version = 1;
            $version->save();

            $course->published_version_id = $version->id;
            $course->published_at = $publishedAt;
        }

        $course->save();
    }

    /**
     * The structure a student who buys this version will see.
     *
     * @return array<string, mixed>
     */
    private function snapshot(Course $course): array
    {
        $sections = Section::query()
            ->where('course_id', $course->id)
            ->orderBy('position')
            ->with('lessons')
            ->get();

        return [
            'title' => $course->title,
            'subtitle' => $course->subtitle,
            'description' => $course->description,
            'level' => $course->level?->value,
            'language' => $course->language?->value,
            'sections' => $sections->map(static fn (Section $section): array => [
                'id' => $section->id,
                'position' => $section->position,
                'title' => $section->title,
                'lessons' => $section->lessons->map(static fn (Lesson $lesson): array => [
                    'id' => $lesson->id,
                    'position' => $lesson->position,
                    'type' => $lesson->type->value,
                    'title' => $lesson->title,
                    'body' => $lesson->body,
                    'media_id' => $lesson->media_id,
                    'duration_sec' => $lesson->duration_sec,
                    'is_preview' => $lesson->is_preview,
                    'unlock_after_days' => $lesson->unlock_after_days,
                ])->all(),
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function video(string $title, int $minutes, bool $preview = false, ?int $unlockAfterDays = null): array
    {
        return [
            'type' => LessonTypeEnum::VIDEO, 'title' => $title, 'body' => null,
            'duration_sec' => $minutes * 60, 'is_preview' => $preview, 'unlock_after_days' => $unlockAfterDays,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function text(string $title, int $minutes): array
    {
        return [
            'type' => LessonTypeEnum::TEXT, 'title' => $title,
            'body' => "## {$title}\n\nDemo lesson text. Read it, then try the examples in your own project.",
            'duration_sec' => $minutes * 60, 'is_preview' => false, 'unlock_after_days' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function quiz(string $title): array
    {
        return [
            'type' => LessonTypeEnum::QUIZ, 'title' => $title, 'body' => 'Check yourself before moving on.',
            'duration_sec' => 300, 'is_preview' => false, 'unlock_after_days' => null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function courses(): array
    {
        return [
            [
                'slug' => 'laravel-api-from-zero', 'category' => 'php-laravel', 'status' => CourseStatusEnum::PUBLISHED,
                'title' => 'Laravel API from zero', 'subtitle' => 'Build a production-ready REST API',
                'description' => 'Routing, validation, Eloquent, auth with JWT and tests: everything for your first real API.',
                'level' => CourseLevelEnum::BEGINNER, 'language' => LanguageEnum::EN, 'price' => 1999, 'currency' => CurrencyEnum::USD,
                'sections' => [
                    ['title' => 'Introduction', 'lessons' => [
                        self::video('What we are going to build', 4, preview: true),
                        self::text('Installing PHP, Composer and Laravel', 10),
                    ]],
                    ['title' => 'Routing and controllers', 'lessons' => [
                        self::video('Routes and route model binding', 14),
                        self::video('Thin controllers', 11),
                        self::quiz('Routing quiz'),
                    ]],
                    ['title' => 'Eloquent', 'lessons' => [
                        self::video('Models, casts and relations', 18),
                        self::text('N+1 queries and eager loading', 8),
                        self::video('Repositories: when they help', 12, unlockAfterDays: 7),
                    ]],
                ],
            ],
            [
                'slug' => 'postgresql-for-developers', 'category' => 'databases', 'status' => CourseStatusEnum::PUBLISHED,
                'title' => 'PostgreSQL для розробників', 'subtitle' => 'Індекси, транзакції, блокування',
                'description' => 'Як Postgres виконує запити, коли допомагає індекс і як не отримати deadlock.',
                'level' => CourseLevelEnum::INTERMEDIATE, 'language' => LanguageEnum::UA, 'price' => 49900, 'currency' => CurrencyEnum::UAH,
                'sections' => [
                    ['title' => 'Основи', 'lessons' => [
                        self::video('Чому Postgres', 6, preview: true),
                        self::text('Типи даних, яких бракує в MySQL', 12),
                    ]],
                    ['title' => 'Індекси', 'lessons' => [
                        self::video('B-tree та EXPLAIN ANALYZE', 20),
                        self::video('Часткові та складені індекси', 15),
                        self::quiz('Тест по індексах'),
                    ]],
                    ['title' => 'Транзакції', 'lessons' => [
                        self::video('Рівні ізоляції', 17),
                        self::text('SELECT ... FOR UPDATE на практиці', 9),
                    ]],
                ],
            ],
            [
                'slug' => 'figma-ui-basics', 'category' => 'ui-ux', 'status' => CourseStatusEnum::PUBLISHED,
                'title' => 'Figma UI basics', 'subtitle' => 'From wireframe to clickable prototype',
                'description' => 'A free intro to interface design in Figma.',
                'level' => CourseLevelEnum::ALL_LEVELS, 'language' => LanguageEnum::EN, 'price' => 0, 'currency' => CurrencyEnum::USD,
                'sections' => [
                    ['title' => 'Getting started', 'lessons' => [
                        self::video('Figma interface tour', 7, preview: true),
                        self::video('Frames and auto layout', 13),
                    ]],
                    ['title' => 'Prototyping', 'lessons' => [
                        self::video('Interactions and flows', 10),
                        self::quiz('Prototype checklist'),
                    ]],
                ],
            ],
            [
                'slug' => 'docker-for-php-devs', 'category' => 'devops', 'status' => CourseStatusEnum::REVIEW,
                'title' => 'Docker for PHP developers', 'subtitle' => 'Local environment that matches production',
                'description' => 'Dockerfile, compose, PHP-FPM + Nginx + Postgres, and a CI pipeline.',
                'level' => CourseLevelEnum::INTERMEDIATE, 'language' => LanguageEnum::EN, 'price' => 2999, 'currency' => CurrencyEnum::EUR,
                'sections' => [
                    ['title' => 'Containers', 'lessons' => [
                        self::video('Images vs containers', 9, preview: true),
                        self::text('Writing a Dockerfile for PHP-FPM', 14),
                    ]],
                    ['title' => 'Compose', 'lessons' => [
                        self::video('Nginx + PHP + Postgres', 16),
                    ]],
                ],
            ],
            [
                'slug' => 'modern-javascript-draft', 'category' => 'javascript', 'status' => CourseStatusEnum::DRAFT,
                'title' => 'Modern JavaScript', 'subtitle' => null,
                'description' => null,
                'level' => null, 'language' => LanguageEnum::EN, 'price' => 1499, 'currency' => CurrencyEnum::USD,
                'sections' => [
                    ['title' => 'Draft section', 'lessons' => [
                        self::video('Video not uploaded yet', 0),
                    ]],
                ],
            ],
            [
                'slug' => 'product-discovery-archived', 'category' => 'product-management', 'status' => CourseStatusEnum::ARCHIVED,
                'title' => 'Product discovery (2025)', 'subtitle' => 'Archived edition',
                'description' => 'Kept for students who bought it; not sold anymore.',
                'level' => CourseLevelEnum::BEGINNER, 'language' => LanguageEnum::EN, 'price' => 999, 'currency' => CurrencyEnum::USD,
                'sections' => [
                    ['title' => 'Discovery', 'lessons' => [
                        self::video('Problem interviews', 12, preview: true),
                        self::text('Opportunity solution tree', 6),
                    ]],
                ],
            ],
        ];
    }
}
