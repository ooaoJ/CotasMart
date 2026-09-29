<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->decimal('shipping_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2);
            $table->boolean('availability')->default(true);
            $table->timestamp('collected_at')->index();
            $table->timestamps();

            $table->index(['offer_id', 'collected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_snapshots');
    }
};

