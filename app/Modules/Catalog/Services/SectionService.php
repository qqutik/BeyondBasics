<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\DTO\Section\SectionReorderDTO;
use App\Modules\Catalog\DTO\Section\SectionStoreDTO;
use App\Modules\Catalog\DTO\Section\SectionUpdateDTO;
use App\Modules\Catalog\Models\Course;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\Repositories\SectionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class SectionService
{
    public function __construct(private SectionRepository $repository) {}

    public function store(SectionStoreDTO $DTO, Course $course): Section
    {
        return $this->repository->store($DTO, $course);
    }

    public function update(SectionUpdateDTO $DTO, Section $section): Section
    {
        return $this->repository->update($DTO, $section);
    }

    public function destroy(Section $section): Section
    {
        return $this->repository->destroy($section);
    }

    /**
     * @return Collection<int, Section> sections in the new order
     */
    public function reorder(SectionReorderDTO $DTO, Course $course): Collection
    {
        return DB::transaction(function () use ($DTO, $course): Collection {
            Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();

            $current = $this->repository->idsOfCourse($course);
            $sent = $DTO->ids;

            sort($current);
            sort($sent);

            if ($current !== $sent) {
                throw ValidationException::withMessages([
                    'ids' => 'The ids must contain every section of this course exactly once.',
                ]);
            }

            $this->repository->updatePositions($course, $DTO->ids);

            return $this->repository->findAllByCourse($course);
        });
    }
}
