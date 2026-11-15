<?php

declare(strict_types=1);

use App\Modules\Billing\Enums\OrderStatusEnum;

it('moves a pending order to paid, failed, expired or canceled only', function () {
    expect(OrderStatusEnum::PENDING->allowedTransitions())->toBe([
        OrderStatusEnum::SUCCESS, OrderStatusEnum::FAILED, OrderStatusEnum::EXPIRED, OrderStatusEnum::CANCELED,
    ])
        ->and(OrderStatusEnum::PENDING->canTransitionTo(OrderStatusEnum::REFUNDED))->toBeFalse();
});

it('refunds only paid orders', function () {
    expect(OrderStatusEnum::SUCCESS->canTransitionTo(OrderStatusEnum::REFUNDED))->toBeTrue()
        ->and(OrderStatusEnum::SUCCESS->canTransitionTo(OrderStatusEnum::PARTIALLY_REFUNDED))->toBeTrue()
        ->and(OrderStatusEnum::PARTIALLY_REFUNDED->canTransitionTo(OrderStatusEnum::PARTIALLY_REFUNDED))->toBeTrue()
        ->and(OrderStatusEnum::PARTIALLY_REFUNDED->canTransitionTo(OrderStatusEnum::REFUNDED))->toBeTrue()
        ->and(OrderStatusEnum::SUCCESS->canTransitionTo(OrderStatusEnum::PENDING))->toBeFalse();
});

it('treats failed, expired, canceled and refunded as final', function (OrderStatusEnum $status) {
    expect($status->allowedTransitions())->toBe([]);
})->with([OrderStatusEnum::FAILED, OrderStatusEnum::EXPIRED, OrderStatusEnum::CANCELED, OrderStatusEnum::REFUNDED]);

it('splits statuses into paid and abandoned without overlap', function () {
    $paid = array_filter(OrderStatusEnum::cases(), fn (OrderStatusEnum $s): bool => $s->wasPaid());
    $abandoned = array_filter(OrderStatusEnum::cases(), fn (OrderStatusEnum $s): bool => $s->isAbandoned());

    expect(array_values($paid))->toBe([OrderStatusEnum::SUCCESS, OrderStatusEnum::PARTIALLY_REFUNDED, OrderStatusEnum::REFUNDED])
        ->and(array_values($abandoned))->toBe([OrderStatusEnum::FAILED, OrderStatusEnum::EXPIRED, OrderStatusEnum::CANCELED]);
});
