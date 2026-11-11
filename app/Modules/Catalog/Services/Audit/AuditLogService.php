<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services\Audit;


use App\Modules\Catalog\DTO\Audit\AuditDTO;
use Illuminate\Support\Facades\Log;

final readonly class AuditLogService implements AuditContract
{

    public function log(AuditDTO $DTO): void
    {
        Log::channel($DTO->channel ?? 'daily')->info($DTO->toArray());
    }

}
