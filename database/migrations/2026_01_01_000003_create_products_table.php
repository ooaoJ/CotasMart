<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 180);
            $table->string('slug', 200)->unique();
            $table->string('model', 120)->nullable()->index();
            $table->string('gtin', 14)->nullable()->unique();
            $table->text('description')->nullable();
            $table->json('specifications')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('popularity_score')->default(0)->index();
            $table->unsignedInteger('search_count')->default(0);
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamp('next_update_at')->nullable()->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();

            $table->index(['category_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

