<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRockTheVoteReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rock_the_vote_reports', function (Blueprint $table) {
            $table->integer('id');
            $table->string('status')->index();
            $table->dateTime('since')->nullable();
            $table->dateTime('before')->nullable();
            $table->timestamps();
            $table->dateTime('dispatched_at')->nullable();
            $table->primary(['id']);
            $table->index(['status', 'dispatched_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rock_the_vote_reports');
    }
}
