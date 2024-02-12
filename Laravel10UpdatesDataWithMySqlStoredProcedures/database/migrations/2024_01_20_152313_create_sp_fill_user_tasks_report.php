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
create definer = superadmin@localhost procedure sp_fillUserTasksReport(OUT out_returnCode bigint unsigned)
BEGIN
    -- sp_fillUserTasksReport( @out_returnCode )
    -- select  @out_returnCode
    DECLARE userId int unsigned;
    DECLARE userName varchar(100);
    DECLARE userEmail varchar(100);
    DECLARE activeTasksCount int unsigned;
    DECLARE completedTasksCount int unsigned;
    DECLARE hasActiveTeamLeadTasks int unsigned;
    DECLARE taskJson json;

    DECLARE done INT DEFAULT FALSE;
    DECLARE usersCursor cursor for SELECT users.id, users.name, users.email FROM users ORDER BY users.id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    CREATE TEMPORARY TABLE IF NOT EXISTS tempUserTasksReport
    (
        user_id                    bigint unsigned,
        user_name                  varchar(100),
        user_email                 varchar(100),
        active_tasks_count         int unsigned,
        completed_tasks_count      int unsigned,
        has_active_team_lead_tasks int unsigned,
        assigned_tasks             json DEFAULT NULL
    );

    SET out_returnCode := 0;

    open usersCursor;
    usersLoop:
    loop
        -- get all users in db
        fetch usersCursor into userId, userName, userEmail;
        IF done THEN LEAVE usersLoop; END IF;

        select count(*) into activeTasksCount from users
        where userId in (select distinct task_user.user_id
                         from task_user, tasks
                         where tasks.status = 'T' and task_user.task_id = tasks.id);

        select count(*) into completedTasksCount from users where userId in (select distinct task_user.user_id
                         from task_user,  tasks
                         where tasks.status = 'D' and task_user.task_id = tasks.id);

        select count(*) into hasActiveTeamLeadTasks from users
        where userId in (select distinct task_user.user_id
                         from task_user,  tasks
                         where tasks.status = 'T' and task_user.task_id = tasks.id
                           and task_user.team_lead = 1);

        -- Construct the JSON object with task details
        SELECT JSON_ARRAYAGG(JSON_OBJECT('id', task_user.task_id, 'title', tasks.title))
        INTO taskJson
        FROM task_user,
             tasks
        WHERE task_user.user_id = userId
          AND task_user.task_id = tasks.id;

        INSERT INTO tempUserTasksReport (user_id, user_name, user_email, active_tasks_count, completed_tasks_count,
                                         has_active_team_lead_tasks, assigned_tasks)
        VALUES (userId, userName, userEmail, activeTasksCount, completedTasksCount, hasActiveTeamLeadTasks, taskJson);

        SET out_returnCode := out_returnCode + 1;

    end loop usersLoop;
    close usersCursor;

    DELETE FROM user_tasks_report;

    INSERT INTO user_tasks_report (user_id, user_name, user_email, active_tasks, completed_tasks,
                                   has_active_team_lead_tasks, assigned_tasks, info, created_at)
    select user_id, user_name, user_email, active_tasks_count, completed_tasks_count, has_active_team_lead_tasks, assigned_tasks,
        CASE
            WHEN active_tasks_count > 0 AND has_active_team_lead_tasks > 0 THEN 'Team Lead with active tasks'
            WHEN active_tasks_count = 0 AND has_active_team_lead_tasks > 0 THEN 'Team Lead withoout active tasks'
            WHEN active_tasks_count > 0 AND has_active_team_lead_tasks = 0 THEN 'Has active tasks'
            WHEN active_tasks_count = 0 AND has_active_team_lead_tasks = 0 THEN 'Has no active tasks'
        ELSE
           'Status unknown'
        END,
    NOW() from tempUserTasksReport;

    DROP TEMPORARY TABLE IF EXISTS tempUserTasksReport;

END;";
        DB::unprepared("DROP procedure IF EXISTS sp_fillUserTasksReport");
        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP procedure IF EXISTS sp_fillUserTasksReport");
    }

};
