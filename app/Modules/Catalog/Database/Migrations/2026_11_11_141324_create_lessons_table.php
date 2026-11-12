<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->unsignedInteger('position');

            $table->string('type', 20);            // LessonTypeEnum
            $table->string('title', 160);
            $table->text('body')->nullable();
            $table->unsignedBigInteger('media_id')->nullable();

            $table->unsignedInteger('duration_sec')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->unsignedSmallInteger('unlock_after_days')->nullable();

            $table->timestamps();

            $table->index(['section_id', 'position']);
            $table->index('media_id');
        });

        DB::statement('ALTER TABLE lessons ADD CONSTRAINT lessons_position_positive CHECK (position > 0)');
        DB::statement('ALTER TABLE lessons ADD CONSTRAINT lessons_duration_non_negative CHECK (duration_sec >= 0)');
        DB::statement('ALTER TABLE lessons ADD CONSTRAINT lessons_unlock_non_negative CHECK (unlock_after_days IS NULL OR unlock_after_days >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
