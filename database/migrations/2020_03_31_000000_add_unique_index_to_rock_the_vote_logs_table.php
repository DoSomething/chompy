<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUniqueIndexToRockTheVoteLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rock_the_vote_logs', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'started_registration']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rock_the_vote_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status', 'started_registration']);
        });
    }
}
