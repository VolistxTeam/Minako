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
        Schema::create('ohys_torrent', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uniqueID', 8);
            $table->string('releaseGroup')->nullable();
            $table->string('broadcaster')->nullable();
            $table->text('title')->nullable();
            $table->float('episode')->nullable();
            $table->text('torrentName')->nullable();
            $table->string('info_totalHash', 40)->nullable();
            $table->string('info_totalSize')->nullable();
            $table->dateTime('info_createdDate')->nullable();
            $table->longText('info_torrent_announces')->nullable();
            $table->longText('info_torrent_files')->nullable();
            $table->string('metadata_video_resolution')->nullable();
            $table->string('metadata_video_codec')->nullable();
            $table->string('metadata_audio_codec')->nullable();
            $table->text('hidden_download_magnet')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ohys_torrent');
    }
};
