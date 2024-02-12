<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('todo_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_id')->references('id')->on('todo')->onDelete('CASCADE');

            $table->string('name', 255);
            $table->mediumText('description')->nullable();
            $table->date('deadline');

            $table->enum('status', ['U', 'D', 'C'])->default('U')->comment('U=>Uncompleted , D=>Disabled, C=>Completed');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index(['todo_id', 'status', 'deadline'], 'todo_task_todo_id_status_deadline_index');
            $table->unique(['todo_id', 'name'], 'todo_task_todo_id_name_unique');
        });
        Artisan::call('db:seed', array('--class' => 'todoTaskWithInitData'));


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('todo_task');
    }
};
