<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('path');
            $table->string('name');
            $table->string('hash')->unique();
            $table->string('video_id')->nullable();
            $table->string('upload_server_uri')->nullable();
            $table->string('endpoint')->nullable();
            $table->string('security_token')->nullable();
            $table->string('oss_bucket')->nullable();
            $table->string('oss_object')->nullable();
            $table->string('temp_access_id')->nullable();
            $table->string('temp_access_secret')->nullable();
            $table->string('expire_time')->nullable();
            $table->integer('slice_size')->nullable();
            $table->integer('uploaded_slices')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('videos');
    }
}
