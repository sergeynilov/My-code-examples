<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        $procedure = "
CREATE PROCEDURE sp_getObjectsCount(
    IN in_objectType varchar(100),
    OUT out_retCount bigint unsigned,
    OUT out_totalCount bigint unsigned)
BEGIN

    IF in_objectType = 'users_without_active_tasks' THEN
        select count(*)
        into out_retCount
        from users
        where users.id not in (select distinct tasks.creator_id from tasks where tasks.status = 'T');
        select count(*) into out_totalCount from users;
    END IF;

    IF in_objectType = 'users_without_completed_tasks' THEN
        select count(*)
        into out_retCount
        from users
        where users.id not in (select distinct tasks.creator_id from tasks where tasks.status = 'D');
        select count(*) into out_totalCount from users;
    END IF;

END;
";
        DB::unprepared("DROP procedure IF EXISTS sp_getObjectsCount");
        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP procedure IF EXISTS sp_getObjectsCount");
    }

};
