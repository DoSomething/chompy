<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class RemovePasswordResetsAndStatsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('stats');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('stats', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('total_records');
            $table->text('filename')->after('id');
            $table->text('stats')->nullable();
            $table->timestamps();
        });
    }
}
