<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('website_url');
            $table->string('logo')->nullable();
            $table->string('source_type', 30)->default('store');
            $table->decimal('reliability_score', 5, 2)->default(100);
            $table->boolean('active')->default(true)->index();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};

