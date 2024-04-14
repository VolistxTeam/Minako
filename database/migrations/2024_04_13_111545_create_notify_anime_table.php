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
        Schema::create('notify_anime', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('uniqueID', 8)->nullable();
            $table->string('notifyID', 15)->nullable();
            $table->string('type')->nullable();
            $table->text('title_canonical')->nullable();
            $table->text('title_romaji')->nullable();
            $table->text('title_english')->nullable();
            $table->text('title_japanese')->nullable();
            $table->text('title_hiragana')->nullable();
            $table->longText('title_synonyms')->nullable();
            $table->text('summary')->nullable();
            $table->string('status')->nullable();
            $table->longText('genres')->nullable();
            $table->string('startDate', 10)->nullable();
            $table->string('endDate', 10)->nullable();
            $table->integer('episodeCount')->nullable();
            $table->integer('episodeLength')->nullable();
            $table->string('source')->nullable();
            $table->string('image_extension', 7)->nullable();
            $table->integer('image_width')->nullable();
            $table->integer('image_height')->nullable();
            $table->text('firstChannel')->nullable();
            $table->double('rating_overall')->nullable();
            $table->double('rating_story')->nullable();
            $table->double('rating_visuals')->nullable();
            $table->double('rating_soundtrack')->nullable();
            $table->longText('trailers')->nullable();
            $table->longText('n_episodes')->nullable();
            $table->longText('mappings')->nullable();
            $table->longText('studios')->nullable();
            $table->longText('producers')->nullable();
            $table->longText('licensors')->nullable();
            $table->tinyInteger('isHidden')->nullable()->default(0);
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent();
        });

        Schema::table('notify_anime', function (Blueprint $table) {
            $table->fullText(['title_canonical', 'title_english', 'title_romaji', 'title_japanese', 'title_hiragana', 'title_synonyms'], 'title_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notify_anime');
    }
};
