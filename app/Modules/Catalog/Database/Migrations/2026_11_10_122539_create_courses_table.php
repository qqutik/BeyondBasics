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
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // FK to Identity's users at DB level only: no Eloquent relation across modules.
            // restrict: an author with courses is anonymized (GDPR), never hard-deleted.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            // Nullable while the course is a draft; required to publish (checked in the service).
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();

            $table->string('slug', 160)->unique();
            $table->string('title', 160);
            $table->string('subtitle', 255)->nullable();
            $table->text('description')->nullable();

            // Plain strings, the allowed values live in enums (CourseLevelEnum, LanguageEnum, CourseStatusEnum).
            $table->string('level', 16)->nullable();
            $table->string('language', 8)->nullable();
            $table->string('status', 16)->default('draft');

            // Money in minor units (cents): 1999 + USD = $19.99. 0 = free course.
            $table->unsignedInteger('price')->default(0);
            $table->char('currency', 3)->default('USD');

            // Points to course_versions; the FK is added in the course_versions migration.
            $table->unsignedBigInteger('published_version_id')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Public catalog: "published, newest first", optionally by category.
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            // Postgres does not index foreign keys automatically; the author's dashboard lists by author_id.
            $table->index('user_id');
        });

        // Postgres has no unsigned integers: "unsignedInteger" is a plain integer there, so enforce it.
        DB::statement('ALTER TABLE courses ADD CONSTRAINT courses_price_non_negative CHECK (price >= 0)');
        DB::statement("ALTER TABLE courses ADD CONSTRAINT courses_currency_iso CHECK (currency ~ '^[A-Z]{3}$')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
