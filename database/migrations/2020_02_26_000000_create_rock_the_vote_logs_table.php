<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRockTheVoteLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rock_the_vote_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('import_file_id')->index();
            $table->string('user_id')->index();
            $table->string('tracking_source');
            $table->string('status');
            $table->string('pre_registered');
            $table->string('started_registration');
            $table->string('finish_with_state');
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
        Schema::dropIfExists('rock_the_vote_logs');
    }
}
