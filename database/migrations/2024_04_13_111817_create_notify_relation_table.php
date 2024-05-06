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
        Schema::create('notify_relation', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('uniqueID', 8)->nullable();
            $table->string('notifyID', 15)->nullable();
            $table->longText('items')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notify_relation');
    }
};
