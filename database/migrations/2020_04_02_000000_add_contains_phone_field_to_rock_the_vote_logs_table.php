<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContainsPhoneFieldToRockTheVoteLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rock_the_vote_logs', function (Blueprint $table) {
            $table->boolean('contains_phone')->after('finish_with_state')->nullable();
            $table->index(['user_id', 'contains_phone', 'started_registration'], 'user_registration_contains_phone');
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
            $table->dropColumn('contains_phone');
            $table->dropIndex('user_registration_contains_phone');
        });
    }
}
