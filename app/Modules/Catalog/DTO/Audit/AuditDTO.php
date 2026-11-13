<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO\Audit;

use App\Modules\Catalog\DTO\BaseDTO;

final class AuditDTO extends BaseDTO
{
    /**
     * @param  array<string, mixed>|null  $additional  extra context for the log entry
     */
    public function __construct(
        public readonly ?int $user_id,
        public readonly ?string $message,

        public readonly ?int $instance_id,
        public readonly ?string $instance_type,
        public readonly ?string $ip,
        public readonly ?array $additional,

        public readonly ?string $channel,
    ) {}
}
