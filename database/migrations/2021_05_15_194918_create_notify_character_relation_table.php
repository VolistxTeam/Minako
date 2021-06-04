<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotifyCharacterRelationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notify_character_relation', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('uniqueID', 8)->nullable()->unique('uniqueID-Unique');
            $table->string('notifyID', 15)->nullable()->unique('notifyID-Unique');
            $table->longText('items')->nullable();
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
        Schema::dropIfExists('notify_character_relation');
    }
}
