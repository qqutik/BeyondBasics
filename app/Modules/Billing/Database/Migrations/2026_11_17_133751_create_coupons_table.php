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
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();

            $table->string('code', 32)->unique();

            $table->string('type', 16);
            $table->unsignedInteger('value');
            $table->char('currency', 3)->nullable();

            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);

            $table->timestamp('valid_from')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->foreignId('course_id')->nullable()->constrained('courses')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->index('course_id');
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE coupons ADD CONSTRAINT coupons_code_format CHECK (code ~ '^[A-Z0-9_-]{3,32}$')");
        DB::statement("ALTER TABLE coupons ADD CONSTRAINT coupons_currency_iso CHECK (currency IS NULL OR currency ~ '^[A-Z]{3}$')");
        DB::statement("ALTER TABLE coupons ADD CONSTRAINT coupons_type_value CHECK (
            (type = 'percent' AND value BETWEEN 1 AND 100 AND currency IS NULL)
            OR (type = 'fixed' AND value > 0 AND currency IS NOT NULL)
        )");
        // Postgres has no unsigned integers: "unsignedInteger" is a plain integer there, so enforce it.
        DB::statement('ALTER TABLE coupons ADD CONSTRAINT coupons_uses_valid CHECK (
            used_count >= 0 AND (max_uses IS NULL OR (max_uses > 0 AND used_count <= max_uses))
        )');
        DB::statement('ALTER TABLE coupons ADD CONSTRAINT coupons_dates_order CHECK (
            valid_from IS NULL OR expires_at IS NULL OR expires_at > valid_from
        )');

        // orders.coupon_id was created without a FK because coupons did not exist yet.
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('coupon_id')->references('id')->on('coupons')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['coupon_id']);
        });

        Schema::dropIfExists('coupons');
    }
};
