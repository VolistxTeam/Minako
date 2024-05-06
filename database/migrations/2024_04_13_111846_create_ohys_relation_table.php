<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ohys_relation', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('uniqueID', 8)->index('uniqueID');
            $table->string('matchingID', 8);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ohys_relation');
    }
};
