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
//        Artisan::call('db:seed', array('--class' => 'tasksWithInitData'));

        $triggerCode = "
create definer = superadmin@localhost trigger trigger_task_insert_after
    before insert
    on tasks
    for each row
BEGIN
    SET @currentUser = SUBSTRING_INDEX(CURRENT_USER(), '@', 1);

    IF (@currentUser = 'superadmin') THEN
         SIGNAL SQLSTATE '45000'
             SET MESSAGE_TEXT = 'Superadmin can not insert task !';
     END IF;

END;
";
        DB::unprepared("DROP TRIGGER IF EXISTS trigger_task_insert_after;");
        DB::unprepared($triggerCode);


        $triggerCode = "
DROP TRIGGER IF EXISTS trigger_task_update_before;
create definer = superadmin@localhost trigger trigger_task_update_before
    before update
    on tasks
    for each row
BEGIN
    SET @taskStatus = NEW.status;
    SET @currentUser = SUBSTRING_INDEX(CURRENT_USER(),'@',1);

    IF @taskStatus = 'D' THEN -- only superadmin can edit completed task
        IF (@currentUser != 'superadmin') THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Only superadmin can edit completed task !';
        END IF;
    END IF;
END;
";
        DB::unprepared("DROP TRIGGER IF EXISTS trigger_task_update_before;");
        DB::unprepared($triggerCode);


        $triggerCode = "
create definer = superadmin@localhost trigger trigger_task_update_after
    after update
    on tasks
    for each row
BEGIN
    SET @newStatus = NEW.status;
    SET @properties =  JSON_OBJECT('action', 'Task was updated AS active IN TRIGGER', 'currentUser', @currentUser);

    INSERT INTO `activity_log` (`log_name`, `description`, `causer_type`, `event`, `properties`, `created_at`)
    VALUES ('default', concat('task trigger_update newStatus : ', @newStatus), 'App\\Models\\User', 'TasksWithSearch event IN TRIGGER',  @properties, NOW() );
END;
";
        DB::unprepared("DROP TRIGGER IF EXISTS trigger_task_update_after;");
        DB::unprepared($triggerCode);


        $triggerCode = "
create definer = superadmin@localhost trigger trigger_task_delete_before
    before delete
    on tasks
    for each row
BEGIN
    SET @taskStatus = OLD.status;
    SET @currentUser = SUBSTRING_INDEX(CURRENT_USER(), '@', 1);

    IF @taskStatus = 'D' THEN -- only superadmin can deleted completed task
        IF (@currentUser != 'superadmin') THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Only superadmin can delete task !';
        END IF;
    END IF;

END;
";
        DB::unprepared("DROP TRIGGER IF EXISTS trigger_task_delete_before;");
        DB::unprepared($triggerCode);

    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS trigger_task_insert_after;");
        DB::unprepared("DROP TRIGGER IF EXISTS trigger_task_update_after;");
        DB::unprepared("DROP TRIGGER IF EXISTS trigger_task_update_before;");
        DB::unprepared("DROP TRIGGER IF EXISTS trigger_task_delete_before;");
    }

};
