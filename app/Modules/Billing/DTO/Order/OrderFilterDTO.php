<?php

declare(strict_types=1);

namespace App\Modules\Billing\DTO\Order;

use App\Modules\Billing\DTO\BaseDTO;
use App\Modules\Billing\Enums\OrderStatusEnum;
use App\Shared\Enums\CurrencyEnum;
use Illuminate\Support\Carbon;

final class OrderFilterDTO extends BaseDTO
{
    public function __construct(
        public readonly ?CurrencyEnum $currency,
        public readonly ?OrderStatusEnum $status,
        public readonly ?Carbon $date_from,
        public readonly ?Carbon $date_to,

        public readonly int $perPage,
    ) {}
}
