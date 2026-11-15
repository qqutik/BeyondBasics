<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Payment processor of the order, values live in PaymentProviderEnum. No CHECK on purpose:
            // a new provider is a new enum case, not a schema change.
            $table->string('provider', 16);

            // FK to Identity's users at DB level only: no Eloquent relation across modules.
            // restrict: orders are financial records, a deleted user is anonymized (GDPR), never hard-deleted.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // Ids in the provider's system. checkout_id: the checkout/payment session, known right after
            // it is created. payment_id: the payment itself (Stripe PaymentIntent), comes with the webhook.
            $table->string('payment_id')->nullable();
            $table->string('checkout_id')->nullable();

            // Plain string, the allowed values live in OrderStatusEnum (and in the CHECK below).
            $table->string('status', 24)->default('pending');

            // Money in minor units (cents), one currency for the whole order.
            // total = subtotal - discount, enforced by a CHECK.
            $table->unsignedInteger('total');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount')->default(0);
            $table->char('currency', 3);

            // Points to coupons; the FK is added in the coupons migration.
            $table->unsignedBigInteger('coupon_id')->nullable();

            $table->timestamp('paid_at')->nullable();
            // No soft deletes: orders are never deleted, only change status.
            $table->timestamps();

            // Webhooks find the order by the provider's ids. Ids of different providers may collide,
            // so they are unique per provider. NULLs do not clash in a Postgres unique index.
            $table->unique(['provider', 'payment_id']);
            $table->unique(['provider', 'checkout_id']);

            // "My orders", newest first. Also covers the user_id FK (Postgres does not index FKs).
            $table->index(['user_id', 'created_at']);
        });

        $statuses = "'pending', 'success', 'failed', 'expired', 'canceled', 'partially_refunded', 'refunded'";

        // Postgres has no unsigned integers: "unsignedInteger" is a plain integer there, so enforce it.
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_valid CHECK (status IN ({$statuses}))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_currency_iso CHECK (currency ~ '^[A-Z]{3}$')");
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_amounts_non_negative
            CHECK (subtotal >= 0 AND discount >= 0 AND total >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_matches CHECK (total = subtotal - discount)');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_paid_at_when_paid
            CHECK (paid_at IS NOT NULL OR status NOT IN ('success', 'partially_refunded', 'refunded'))");

        // At most one pending order per user: a double click on "Buy" cannot open two checkout sessions.
        // A new checkout cancels the previous pending order first.
        DB::statement("CREATE UNIQUE INDEX orders_one_pending_per_user ON orders (user_id) WHERE status = 'pending'");

        // A coupon counts once per user while the order holds it (pending) or used it (success, even if refunded).
        // Failed / expired / canceled orders release it. Closes the race of two parallel checkouts with one coupon.
        DB::statement("CREATE UNIQUE INDEX orders_coupon_once_per_user ON orders (user_id, coupon_id)
            WHERE coupon_id IS NOT NULL AND status NOT IN ('failed', 'expired', 'canceled')");

        // Scheduler: "pending orders older than the checkout TTL". Small: only pending rows are indexed.
        DB::statement("CREATE INDEX orders_pending_created_at ON orders (created_at) WHERE status = 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
