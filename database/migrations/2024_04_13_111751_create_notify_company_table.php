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
        Schema::create('notify_company', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('uniqueID', 8)->nullable();
            $table->string('notifyID', 15)->nullable();
            $table->text('name_english')->nullable();
            $table->text('name_japanese')->nullable();
            $table->longText('name_synonyms')->nullable();
            $table->mediumText('description')->nullable();
            $table->mediumText('email')->nullable();
            $table->longText('links')->nullable();
            $table->longText('mappings')->nullable();
            $table->longText('location')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent();
        });

        Schema::table('notify_company', function (Blueprint $table) {
            $table->fullText(['name_english', 'name_japanese', 'name_synonyms'], 'company_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notify_company');
    }
};
