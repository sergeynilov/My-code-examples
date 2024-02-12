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
CREATE PROCEDURE sp_taskInsert(IN in_parentId bigint unsigned, IN in_creatorId bigint unsigned, IN in_title varchar(255), IN in_categoryId smallint unsigned, IN in_priority varchar(1), IN in_weight int unsigned, IN in_status varchar(255), IN in_description mediumtext, IN in_completed_at datetime, OUT out_returnCode bigint unsigned)
BEGIN

    INSERT INTO tasks(parent_id, creator_id, title, category_id, priority, weight, status, description, completed_at)
    VALUES (in_parentId, in_creatorId, in_title, in_categoryId, in_priority, in_weight, in_status, in_description, in_completed_at);
    SET @taskId = LAST_INSERT_ID();

    if @taskId > 0 then
        SET out_returnCode := @taskId;
    end if;
END;

";
        DB::unprepared("DROP procedure IF EXISTS sp_taskInsert");
        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP procedure IF EXISTS sp_taskInsert");
    }
};
