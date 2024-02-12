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
CREATE PROCEDURE sp_taskDelete(IN in_taskId bigint unsigned, IN in_loggedUserId bigint unsigned, OUT out_returnCode bigint unsigned)
BEGIN
    DECLARE taskCreatorId bigint unsigned;

    SELECT creator_id INTO taskCreatorId from tasks WHERE id = in_taskId;
    IF (in_loggedUserId != taskCreatorId) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Only creator can delete task !';
        SET out_returnCode := -1;
    END IF;

    DELETE FROM tasks WHERE tasks.id = in_taskId;

    SET out_returnCode := in_taskId;

END
";
        DB::unprepared("DROP procedure IF EXISTS sp_taskDelete");
        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP procedure IF EXISTS sp_taskDelete");
    }
};
