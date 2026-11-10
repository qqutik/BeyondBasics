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
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('slug', 120);
            $table->string('name', 120);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'position']);
        });

        DB::statement('CREATE UNIQUE INDEX categories_parent_slug_unique ON categories (parent_id, slug) NULLS NOT DISTINCT');

        DB::statement('ALTER TABLE categories ADD CONSTRAINT categories_not_own_parent CHECK (parent_id <> id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
