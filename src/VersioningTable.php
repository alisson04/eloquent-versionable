<?php

namespace Kiqstyle\EloquentVersionable;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class VersioningTable
{
    public function createVersioningTable($tableName)
    {
        $fields = Schema::getColumnListing($tableName);

        if (! in_array('deleted_at', $fields)) {
            $this->addDeletedAtInTable($tableName);
            $fields[] = 'deleted_at';
        }

        $tableVersioningName = $tableName . '_versioning';
        DB::statement("CREATE TABLE {$tableVersioningName} LIKE {$tableName}");

        $this->dropPrimaryKeyAndAddVersioningFields($tableVersioningName, $tableName);

        $stringFields = "`" . implode("`,`", $fields) . "`";
        DB::statement("INSERT INTO {$tableVersioningName} ({$stringFields}) SELECT {$stringFields} FROM {$tableName}");
    }

    private function addDeletedAtInTable($tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->dateTime('deleted_at')->nullable();
        });
    }

    private function dropPrimaryKeyAndAddVersioningFields(string $tableVersioningName, string $tableName)
    {
        Schema::table($tableVersioningName, function ($table) {
            $table->dropColumn('id');
        });

        Schema::table($tableVersioningName, function ($table) use ($tableName) {
            $table->increments('_id')->first();
            $table->unsignedInteger('id')->after('_id');
            $table->dateTime('next')->nullable();
            $table->foreign('id')->references('id')->on($tableName)->onDelete('cascade');
        });
    }
}
