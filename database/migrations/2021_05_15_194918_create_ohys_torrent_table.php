<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOhysTorrentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ohys_torrent', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uniqueID', 8);
            $table->string('releaseGroup')->nullable();
            $table->text('title')->nullable();
            $table->smallInteger('episode')->nullable();
            $table->text('torrentName')->nullable();
            $table->string('info_totalHash', 40)->nullable();
            $table->string('info_totalSize')->nullable();
            $table->string('info_createdDate')->nullable();
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
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ohys_torrent');
    }
}
