<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOptionsFieldToImportFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('import_files', function (Blueprint $table) {
            $table->string('options')->after('import_type')->nullable()->comment('Parameters passed to the import, like Email Subscription Topic or RTV Report ID.');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('import_files', function (Blueprint $table) {
            $table->dropColumn('options');
        });
    }
}