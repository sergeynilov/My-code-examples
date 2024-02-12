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
CREATE PROCEDURE sp_taskUpdate(IN in_taskId bigint unsigned, IN in_parentId bigint unsigned, IN in_title varchar(255), IN in_categoryId smallint unsigned, IN in_priority varchar(1), IN in_weight int unsigned, IN in_status varchar(255), IN in_description mediumtext, IN in_completed_at datetime, IN in_loggedUserId bigint unsigned, OUT out_returnCode bigint unsigned)
BEGIN
    DECLARE taskCreatorId bigint unsigned;

    SELECT creator_id INTO taskCreatorId from tasks WHERE id = in_taskId;

    IF (in_loggedUserId != taskCreatorId) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Only creator can update task !';
        SET out_returnCode := -1;
    END IF;

    UPDATE tasks
    SET parent_id   = in_parentId,
        title       = in_title,
        category_id = in_categoryId,
        priority    = in_priority,
        weight      = in_weight,
        status      = in_status,
        description = in_description,
        completed_at = in_completed_at
    WHERE id = in_taskId;

    SET out_returnCode := in_taskId;
END;
";
        DB::unprepared("DROP procedure IF EXISTS sp_taskUpdate");
        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP procedure IF EXISTS sp_taskUpdate");
    }
};
