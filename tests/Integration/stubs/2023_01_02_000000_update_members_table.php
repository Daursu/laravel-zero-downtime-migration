<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Daursu\ZeroDowntimeMigration\ZeroDowntimeSchema::table('members', function (Blueprint $table) {
            $table->integer('age')->after('name')->index();
            $table->string('description')->after('age');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \Daursu\ZeroDowntimeMigration\ZeroDowntimeSchema::table('members', function (Blueprint $table) {
            $table->dropColumn('age');
            $table->dropColumn('description');
        });
    }
}
