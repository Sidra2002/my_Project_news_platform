<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('source_news', function (Blueprint $table) {
            $table->string('img_url', 255)->nullable()->change();
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('source_news', function (Blueprint $table) {
             $table->string('img_url', 255)->nullable(false)->change();
            //
        });
    }
};
