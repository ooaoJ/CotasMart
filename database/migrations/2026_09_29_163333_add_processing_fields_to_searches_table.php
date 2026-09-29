<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('searches', function (Blueprint $table) {
            $table
                ->string('status', 30)
                ->default('pending')
                ->index()
                ->after('normalized_query');

            $table
                ->json('filters')
                ->nullable()
                ->after('status');

            $table
                ->text('error_message')
                ->nullable()
                ->after('results_count');

            $table
                ->timestamp('started_at')
                ->nullable()
                ->after('searched_at');

            $table
                ->timestamp('completed_at')
                ->nullable()
                ->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('searches', function (Blueprint $table) {
            $table->dropIndex(['status']);

            $table->dropColumn([
                'status',
                'filters',
                'error_message',
                'started_at',
                'completed_at',
            ]);
        });
    }
};