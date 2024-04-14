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
        Schema::create('mal_anime', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('uniqueID', 8)->nullable();
            $table->string('notifyID', 15)->nullable();
            $table->integer('episode_id')->nullable();
            $table->text('title')->nullable()->index('title');
            $table->text('title_japanese')->nullable()->index('title_japanese');
            $table->text('title_romanji')->nullable()->index('title_romanji');
            $table->dateTime('aired')->nullable();
            $table->tinyInteger('filler')->nullable()->default(0);
            $table->tinyInteger('recap')->nullable()->default(0);
            $table->tinyInteger('isHidden')->nullable()->default(0);
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent();
            $table->index(['uniqueID', 'notifyID', 'episode_id'], 'KEY');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mal_anime');
    }
};
