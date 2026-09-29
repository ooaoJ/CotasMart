<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained()->restrictOnDelete();
            $table->string('external_identifier')->nullable();
            $table->string('title');
            $table->string('seller')->nullable();
            $table->text('url');
            $table->decimal('current_price', 12, 2);
            $table->decimal('shipping_price', 12, 2)->nullable();
            $table->decimal('installment_price', 12, 2)->nullable();
            $table->string('payment_condition')->nullable();
            $table->boolean('availability')->default(true)->index();
            $table->timestamp('last_checked_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['source_id', 'external_identifier']);
            $table->index(['product_id', 'current_price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};

