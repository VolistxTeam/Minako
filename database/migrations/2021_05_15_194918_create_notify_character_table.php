<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotifyCharacterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notify_character', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('uniqueID', 8)->nullable()->unique('UNIQUE');
            $table->string('notifyID', 15)->nullable();
            $table->text('name_canonical')->nullable();
            $table->text('name_english')->nullable();
            $table->text('name_japanese')->nullable();
            $table->longText('name_synonyms')->nullable();
            $table->string('image_extension', 7)->nullable();
            $table->integer('image_width')->nullable();
            $table->integer('image_height')->nullable();
            $table->mediumText('description')->nullable();
            $table->longText('spoilers')->nullable();
            $table->longText('attributes')->nullable();
            $table->longText('mappings')->nullable();
            $table->tinyInteger('isHidden')->nullable()->default(0);
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notify_character');
    }
}
