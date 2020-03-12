<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRowCountFieldToRockTheVoteReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rock_the_vote_reports', function (Blueprint $table) {
            $table->integer('row_count')->after('before')->nullable();
            $table->integer('current_index')->after('row_count')->nullable();
            $table->string('user_id')->after('created_at')->nullable();
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
            $table->dropColumn('row_count');
            $table->dropColumn('current_index');
            $table->dropColumn('user_id');
        });
    }
}
