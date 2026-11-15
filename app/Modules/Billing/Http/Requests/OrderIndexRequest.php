<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Requests;

use App\Modules\Billing\DTO\Order\OrderFilterDTO;
use App\Modules\Billing\Enums\OrderStatusEnum;
use App\Shared\Enums\CurrencyEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

final class OrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'currency' => ['sometimes', Rule::enum(CurrencyEnum::class)],
            'status' => ['sometimes', Rule::enum(OrderStatusEnum::class)],
            'date_from' => ['sometimes', 'date'],
            // Compared with date_from only when it is sent: without it "after_or_equal" would parse the literal string.
            'date_to' => $this->filled('date_from')
                ? ['sometimes', 'date', 'after_or_equal:date_from']
                : ['sometimes', 'date'],

            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toDTO(): OrderFilterDTO
    {
        return new OrderFilterDTO(
            currency: $this->filled('currency') ? $this->enum('currency', CurrencyEnum::class) : null,
            status: $this->filled('status') ? $this->enum('status', OrderStatusEnum::class) : null,
            date_from: $this->filled('date_from') ? $this->date('date_from') : null,
            date_to: $this->filled('date_to') ? $this->date('date_to') : null,

            perPage: $this->integer('per_page', Config::integer('billing.pagination.per_page')),
        );
    }
}
