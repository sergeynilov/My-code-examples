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
create definer = lardev@localhost procedure sp_taskComplete(IN in_taskId bigint unsigned, IN in_completeSubtasks tinyint unsigned, IN in_userId bigint unsigned, OUT out_returnCode bigint unsigned)
BEGIN

    DECLARE taskCreatorId bigint unsigned;

    SELECT creator_id INTO taskCreatorId from tasks WHERE id = in_taskId;

    IF (in_userId != taskCreatorId) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Only creator can complete task !';
        SET out_returnCode := -1;
    END IF;

    SET max_sp_recursion_depth = 255;
    CALL sp_checkCompleteSubtasks(in_taskId, in_completeSubtasks, out_returnCode);

    UPDATE tasks SET status = 'D', completed_at = NOW() WHERE tasks.id = in_taskId;

    SET out_returnCode := in_taskId;

END;
";
        DB::unprepared("DROP procedure IF EXISTS sp_taskComplete");
        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP procedure IF EXISTS sp_taskComplete");
    }
};
