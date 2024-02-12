<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->references('id')->on('tasks')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->foreignId('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->string('title', 255);

            $table->enum('priority', [ '1', '2', '3', '4', '5'])->default("1")->comment('1-Low, 2-Normal, 3-High, 4-Urgent, 5-Immediate  ');
            $table->enum('status', ['D', 'T'])->default('T')->comment('D => Done, T=>Todo');

            $table->mediumText('description');

            $table->timestamp('completed_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'title'], 'tasks_user_id_title_index');

            // Form with search by title/description of task
            // 1) entered search by title/description
            // 2) (optional) by status
            // 3) (optional) by user_id
            // 4) created_at desc (for order)
            $table->index(['title', 'status', 'user_id', 'created_at'], 'tasks_title_status_user_id_created_at_index');


            // Search of subtasks for selected task
            // 1) entered parent_id
            // 2) (optional) by status
            // 3) (optional) by priority
            // 4) created_at desc (for order)
            $table->index(['parent_id', 'status', 'priority', 'created_at'], 'tasks_parent_id_status_priority_created_at_index');


            // When need to check all uncompleted/completed tasks : completed_at desc/asc / priority desc
            // 1)  by status = 'T'
            // 3) (optional) by priority
            // 4) created_at desc (for order)
            $table->index([ 'status', 'priority', 'created_at'], 'tasks_status_priority_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
