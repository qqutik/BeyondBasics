<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services\Audit;

use App\Modules\Catalog\DTO\Audit\AuditDTO;

interface AuditContract
{
    public function log(AuditDTO $DTO): void;

}
