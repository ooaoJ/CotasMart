<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('external_catalog_id', 191)
                ->nullable()
                ->unique()
                ->after('gtin');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique([
                'external_catalog_id',
            ]);

            $table->dropColumn('external_catalog_id');
        });
    }
};