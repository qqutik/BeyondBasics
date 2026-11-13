<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Course;

use App\Modules\Catalog\DTO\BaseDTO;
use App\Modules\Catalog\Enums\Course\CourseLevelEnum;
use App\Modules\Catalog\Enums\Course\CourseStatusEnum;
use App\Modules\Catalog\Enums\CurrencyEnum;
use App\Modules\Catalog\Enums\LanguageEnum;

/**
 * Filters of the course list. null = the filter is not applied.
 * Prices are in minor units (cents), like courses.price: price_max=0 lists free courses.
 * A price range is compared without conversion, so combine it with currency.
 */
final class CourseFilterDTO extends BaseDTO
{
    public function __construct(
        public readonly ?int $user_id,
        public readonly ?int $category_id,
        public readonly ?string $title,
        public readonly ?int $price_min,
        public readonly ?int $price_max,
        public readonly ?CurrencyEnum $currency,
        public readonly ?CourseLevelEnum $level,
        public readonly ?LanguageEnum $language,
        public readonly ?CourseStatusEnum $status,
        public readonly int $perPage,
    ) {}
}
