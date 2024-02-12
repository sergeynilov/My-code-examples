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

CREATE PROCEDURE sp_checkCompleteSubtasks(
    IN in_taskId bigint unsigned,
    IN in_completeSubtasks tinyint unsigned,
    OUT out_returnCode bigint unsigned)

BEGIN
    DECLARE childTaskId smallint unsigned;
    DECLARE childTaskTitle varchar(100);
    DECLARE childTaskStatus varchar(1);
    DECLARE done INT DEFAULT FALSE;
    DECLARE tasksCursor cursor
        for SELECT tasks.id, tasks.title, tasks.status
            FROM tasks
            WHERE tasks.parent_id = in_taskId
            ORDER BY tasks.id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    open tasksCursor;
    childrenTasksLoop:
    loop
        -- get all children of in_taskId and check them

        fetch tasksCursor into childTaskId, childTaskTitle, childTaskStatus;

        IF(in_completeSubtasks = 1 and childTaskStatus != 'D') THEN
            UPDATE tasks SET status = 'D', completed_at = NOW() WHERE tasks.id = childTaskId;
        END IF;

        IF(in_completeSubtasks != 1 and childTaskStatus != 'D') THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Can not complete the task, as it has uncompleted subtask ';
        END IF;

        IF done THEN
            LEAVE childrenTasksLoop;
        END IF;

    end loop childrenTasksLoop;
    close tasksCursor;

    SET out_returnCode := in_taskId;
END;";
        DB::unprepared("DROP procedure IF EXISTS sp_checkCompleteSubtasks");
        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP procedure IF EXISTS sp_checkCompleteSubtasks");
    }
};
