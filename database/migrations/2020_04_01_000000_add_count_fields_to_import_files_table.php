<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCountFieldsToImportFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('import_files', function (Blueprint $table) {
            $table->integer('import_count')->after('row_count')->default(0);
            $table->integer('skip_count')->after('import_count')->default(0);
        });

        \DB::table('import_files')->update(['import_count' => \DB::raw('row_count')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('import_files', function (Blueprint $table) {
            $table->dropColumn('import_count');
            $table->dropColumn('skip_count');
        });
    }
}
