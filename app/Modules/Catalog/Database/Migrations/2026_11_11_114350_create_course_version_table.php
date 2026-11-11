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
        Schema::create('course_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->jsonb('snapshot');
            $table->timestamp('published_at');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['course_id', 'version']);
        });

        DB::statement('ALTER TABLE course_versions ADD CONSTRAINT course_versions_version_positive CHECK (version > 0)');

        Schema::table('courses', function (Blueprint $table) {
            $table->foreign('published_version_id')
                ->references('id')->on('course_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['published_version_id']);
        });

        Schema::dropIfExists('course_versions');
    }
};
