<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\Lesson\LessonTypeEnum;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Section;
use App\Modules\Identity\Models\User;
use Illuminate\Database\QueryException;

function lessonSection(): Section
{
    $course = Course::query()->create(['user_id' => User::factory()->create()->id, 'slug' => 'lessons-course', 'title' => 'Lessons course']);

    return Section::query()->create(['course_id' => $course->id, 'title' => 'Intro', 'position' => 1]);
}

function makeLesson(Section $section, int $position, LessonTypeEnum $type = LessonTypeEnum::TEXT): Lesson
{
    $lesson = new Lesson(['type' => $type, 'title' => "Lesson {$position}"]);
    $lesson->section_id = $section->id;
    $lesson->position = $position;
    $lesson->save();

    return $lesson;
}

it('casts the lesson fields and applies the defaults', function () {
    $lesson = makeLesson(lessonSection(), 1, LessonTypeEnum::VIDEO)->refresh();

    expect($lesson->type)->toBe(LessonTypeEnum::VIDEO)
        ->and($lesson->isVideo())->toBeTrue()
        ->and($lesson->duration_sec)->toBe(0)
        ->and($lesson->is_preview)->toBeFalse()
        ->and($lesson->media_id)->toBeNull()
        ->and($lesson->unlock_after_days)->toBeNull();
});

it('returns the lessons of a section in position order', function () {
    $section = lessonSection();
    makeLesson($section, 2);
    makeLesson($section, 1);

    expect($section->lessons->pluck('position')->all())->toBe([1, 2]);
});

it('deletes the lessons together with their section', function () {
    $section = lessonSection();
    makeLesson($section, 1);

    $section->delete();

    expect(Lesson::query()->count())->toBe(0);
});

it('rejects a non-positive position at the DB level', function () {
    makeLesson(lessonSection(), 0);
})->throws(QueryException::class, 'lessons_position_positive');

it('does not let section_id and position be mass assigned', function () {
    $lesson = new Lesson(['section_id' => 99, 'position' => 5, 'title' => 'x', 'type' => LessonTypeEnum::TEXT]);

    expect($lesson->section_id)->toBeNull()
        ->and($lesson->position)->toBeNull();
});

it('unlocks a lesson after the configured number of days', function () {
    $lesson = new Lesson(['unlock_after_days' => 7]);

    expect($lesson->isUnlockedAfter(6))->toBeFalse()
        ->and($lesson->isUnlockedAfter(7))->toBeTrue()
        ->and((new Lesson)->isUnlockedAfter(0))->toBeTrue();
});
