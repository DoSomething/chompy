<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRetryReportIdFieldToRockTheVoteReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rock_the_vote_reports', function (Blueprint $table) {
            $table->unsignedInteger('retry_report_id')->nullable()->after('current_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rock_the_vote_reports', function (Blueprint $table) {
            $table->dropColumn('retry_report_id');
        });
    }
}
