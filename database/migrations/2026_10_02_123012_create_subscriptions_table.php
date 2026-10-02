<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('provider')->default('mercadopago');

            $table->string('provider_subscription_id')
                ->nullable()
                ->unique();

            $table->decimal('amount', 10, 2);

            $table->string('status', 30)
                ->default('pending')
                ->index();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('next_payment_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};