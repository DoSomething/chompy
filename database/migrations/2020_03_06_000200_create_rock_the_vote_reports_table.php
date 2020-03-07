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
            $table->string('since');
            $table->string('before');
            $table->timestamps();
            $table->date('imported_at')->nullable();
            $table->primary(array('id'));
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
