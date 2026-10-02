<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('subscription_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('provider')
                ->default('mercadopago');

            $table->string('provider_payment_id')
                ->nullable()
                ->unique();

            $table->string('provider_invoice_id')
                ->nullable()
                ->unique();

            $table->decimal('amount', 10, 2);

            $table->string('status', 30)
                ->index();

            $table->timestamp('paid_at')->nullable();

            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};