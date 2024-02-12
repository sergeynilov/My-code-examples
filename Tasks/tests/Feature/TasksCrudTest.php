<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;
use Tests\TestCase;
use App\Models\Task;

class TasksCrudTest extends TestCase
{
    use InteractsWithExceptionHandling;

    protected static $wasSetup = false;
    protected static $loggedUser = null;

    public function setUp(): void
    {
        parent::setUp();
        if ( ! self::$wasSetup) {
            // Regenerate structure / fresh data only once at first test
            /*            Artisan::call(' migrate:fresh --seed');
                        Artisan::call('config:clear');
                        $databaseName = \DB::connection()->getDatabaseName();
                        $result = Str::endsWith($databaseName, 'HttpTesting');
                        if (! $result) { // Check valid database for tests by prefix as I do not use sqlite for testing
                            die('Invalid database "' . $databaseName . '" connected ');
                        }*/
            self::$wasSetup = true;
            self::$loggedUser = User::factory(User::class)->create();
        }
    }
    // public function setUp(): void

    /**
     * 1) Create task from factory and search Task by title field and compare title of task
     */
    public function test_1_FilterOwnerTasks()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $taskSearch = 'Test Task ' . $faker->text(20) . ' Lorem Value';

        // Create 1 new Task for testing
        Task::factory()->userId(self::$loggedUser->id)->create(['title' => $taskSearch]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->postJson(route('tasks.filter'), [  // Index tasks_user_id_title_index is used
                'search' => $taskSearch,
            ]);

        // Check Assert
        $response->assertStatus(JsonResponse::HTTP_OK);  // 200
        $this->assertEquals($response->original->count(), 1, '11 : Number of Tasks found invalid');
    } // 1: FilterOwnerTasks

    /**
     * 2) Create task from factory, save model in storage, Search Task from storage by title field and compare description of task
     */
    public function test_2_TaskIsAdded()
    {
        // Test Data Setup
        $taskModel = Task::factory()->userId(self::$loggedUser->id)->make();  // model only in memory

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->postJson(route('tasks.store'), $taskModel->toArray());
        $response
            ->assertStatus(JsonResponse::HTTP_CREATED); // 201

        // READ TASK CREATED ABOVE
        $insertedTask = Task::getBySearch(search: $taskModel->title, partial: false)
            ->first();

        // Check Assert
        $this->assertNotNull($insertedTask, '21 : Inserted task not found');
        $this->assertEquals(
            $insertedTask->description,
            $taskModel->description,
            '22 : Description read is not equal title on insert'
        );
    }
    // 2: testTaskIsAdded()

    /**
     * 3) Create task from factory, save model in storage, Update model, Search Task from storage by title field and compare description of task
     */
    public function test_3_TaskIsUpdated()
    {
        // Test Data Setup
        $task = Task::factory()->userId(self::$loggedUser->id)->create();

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->putJson(route('tasks.update', $task->id), [
                'title' => $task->title . ' UPDATED',
                'description' => $task->description . ' UPDATED',
                'userId' => $task->userId,
                'status' => $task->status,
                'priority' => $task->priority,
                'completedAt' => $task->completedAt
            ]);
        // Check Assert
        $response->assertStatus(JsonResponse::HTTP_RESET_CONTENT);  // 205
        // READ TASK CREATED ABOVE
        $updatedTask = Task::getBySearch(search: $task->title . ' UPDATED', partial: false)
            ->first();

        $this->assertNotNull($updatedTask, '31 : updated task not found');
        $this->assertEquals(
            $updatedTask->description,
            $task->description . ' UPDATED',
            '32 : Title read is not equal title on update'
        );
    }
    // 3: TaskIsUpdated()

    /**
     * Create task from factory, save model in storage, Try to update model with negative ID - Must return not found response
     */
    public function test_31_NegativeTaskFailuredBeUpdatedAsNotFound()
    {
        // Test Data Setup
        $task = Task::factory()->userId(self::$loggedUser->id)->create([]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->putJson(route('tasks.update', -$task->id), [
                'title' => $task->title . ' UPDATED',
                'description' => $task->description . ' UPDATED',
                'userId' => $task->userId,
                'status' => $task->status,
                'priority' => $task->priority,
                'completedAt' => $task->completedAt
            ]);

        // Check Assert
        $response->assertStatus(JsonResponse::HTTP_NOT_FOUND);  // 404
    }
    // 31: testNegativeTaskFailuredBeUpdatedAsNotFound()

    /**
     * 4) Create task from factory - try to update model, with other user - must catch runtime error
     */
    public function test_4_TaskUpdatingIsFailedOnOtherUser()
    {
        // Test Data Setup
        $anotherUser = User::factory(User::class)->create();
        $task = Task::factory()->userId($anotherUser->id)->create([]);

        // Check Assert for custom Exception
        $this->withoutExceptionHandling();

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->putJson(route('tasks.update', $task->id), [
                'title' => $task->title . ' UPDATED',
                'description' => $task->description . ' UPDATED',
                'userId' => $task->userId,
                'status' => $task->status,
                'priority' => $task->priority,
                'completedAt' => $task->completedAt
            ]);

        $response->assertServerError();
        $response->assertSeeText(__('You are not allowed to edit this task'), false);
    }
    // 4: TaskUpdatingIsFailedOnOtherUser()

    /**
     * 5) Create task from factory, save model in storage, delete task - Must return no content response
     */
    public function test_5_TaskIsDestroyed()
    {
        // Test Data Setup
        $task = Task::factory()->incompleted()->userId(self::$loggedUser->id)->create([]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->delete(route('tasks.destroy', $task->id), []);

        // Check Assert
        $response->assertStatus(JsonResponse::HTTP_NO_CONTENT);  // 204
    }
    // 5: testTaskIsDestroyed()

    /**
     * 6) Create task from factory, save model in storage, delete task - Must return custom error
     */
    public function test_6_TaskDestroyingIsFailedOnOtherUser()
    {
        // Test Data Setup
        $anotherUser = User::factory(User::class)->create();

        $task = Task::factory()->userId($anotherUser->id)->create([]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->delete(route('tasks.destroy', $task->id), []);

        // Check Assert
        $response->assertServerError();
        $response->assertSeeText(__('You are not allowed to delete this task'), false);
    }
    // 6: TaskDestroyingIsFailedOnOtherUser()

    /**
     * 7) Create completed task from factory and try to delete it - Must return custom exception
     */
    public function test_7_TaskDestroyedFailedAsDone()
    {
        // Test Data Setup
        $task = Task::factory()->userId(self::$loggedUser->id)->completed()->create([]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->delete(route('tasks.destroy', $task->id), []);

        // Check Assert
        $response->assertServerError();
        $response->assertSeeText(__('You can not delete completed task'), false);
    }
    // 7: TaskDestroyedFailedAsDone()

    /**
     * 8) Create task from factory, save model in storage, Try to delete model with negative ID - Must return not found response
     */
    public function test_8_NegativeTaskIsDestroyedAsNotFound()
    {
        // Test Data Setup
        $task = Task::factory()->userId(self::$loggedUser->id)->create([]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->delete(route('tasks.destroy', -$task->id), []);

        // Check Assert
        $response->assertStatus(JsonResponse::HTTP_NOT_FOUND);  // 404
    }
    // 8: testTaskIsDestroyed()

    /**
     * 9) Create task from factory in storage and read task from storage
     */
    public function test_9_TaskIsShown()
    {
        // Test Data Setup
        $task = Task::factory()->create();

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->get(route('tasks.show', $task->id));

        // Check Assert
        $response->assertStatus(JsonResponse::HTTP_OK);  // 200
    }
    // 9: TaskIsShown()

    /**
     * 10) Create task from factory, save model in storage, Update model, Search Task from storage by title field and compare title of task
     */
    public function test_10_TaskIsMarkedAsDone()
    {
        // Test Data Setup
        $task = Task::factory()->completed()->userId(self::$loggedUser->id)->create();

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->putJson(route('tasks.done', $task->id), []);

        // Check Assert
        $response->assertStatus(JsonResponse::HTTP_RESET_CONTENT);  // 205
        // READ TASK CREATED ABOVE
        $updatedTask = Task::getByStatus(TaskStatus::DONE->value)->getById($task->id)
            ->first();

        $this->assertNotNull($updatedTask, '10_1 : completed task not found');
        $this->assertEquals(
            $updatedTask->status,
            TaskStatus::DONE->value,
            '10_2 : Status read is not equal complete status'
        );
    }
    // 10 TaskIsMarkedAsDone()


    /////////////// FILTERS ////////////
    /**
     * 11) When need to check all uncompleted/completed tasks : completed_at desc/asc / priority desc
     */
    public function test_11_FilterTasksByPriorityCreatedAt()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $taskSearch = 'Test Task ' . $faker->text(20) . ' Lorem Value';

        // Create 11 new Task for testing
        Task::factory()->userId(self::$loggedUser->id)->create(['title' => $taskSearch, 'status' => TaskStatus::TODO, 'priority' => TaskPriority::HIGH]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->postJson(route('tasks.filter'), [  // Index tasks_user_id_title_index is used
                'search' => $taskSearch,
                'sortBy' => 'priority_created_at',
                'userId' => self::$loggedUser->id,
                'status' => TaskStatus::TODO->value,
                'priority' => TaskPriority::HIGH
            ]);

        // Check Assert
        $response->assertStatus(JsonResponse::HTTP_OK);  // 200
        $this->assertEquals($response->original->count(), 1, '11_1 : Number of Tasks found invalid');
    } // 11: FilterTasksByPriorityCreatedAt

    /**
     * 12) When need to check all uncompleted/completed tasks : completed_at desc/asc / priority desc
     */
    public function test_12_FilterTasksstatusPriorityCreatedAt()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $taskSearch = 'Test Task ' . $faker->text(20) . ' Lorem Value';

        // Create 12 new Task for testing
        Task::factory()->userId(self::$loggedUser->id)->create(['title' => $taskSearch, 'status' => TaskStatus::TODO, 'priority' => TaskPriority::LOW]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->postJson(route('tasks.filter'), [  // Index tasks_status_priority_created_at_index is used
                'search' => $taskSearch,
                'sortBy' => 'status_priority_created_at',
                'userId' => self::$loggedUser->id,
                'status' => TaskStatus::TODO->value,
                'priority' => TaskPriority::LOW
            ]);

        // Check Assert
        $response->assertStatus(JsonResponse::HTTP_OK);  // 200
        $this->assertEquals($response->original->count(), 1, '12_1 : Number of Tasks found invalid');
    } // 12: FilterTasksstatusPriorityCreatedAt

    /**
     * 13) Create task from factory and search Task by title field and compare title of task
     */
    public function test_13_FilterTasksWithParentSubtasks()
    {
        // Test Data Setup
        $faker = \Faker\Factory::create();
        $taskSearch = 'Test Task ' . $faker->text(20) . ' Lorem Value';

        // Create 1 new Task for testing
        Task::factory()->userId(self::$loggedUser->id)->create(['title' => $taskSearch]);

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->postJson(route('tasks.filter'), [
                'search' => $taskSearch,
            ]);

        // Check Assert
        $response->assertStatus(JsonResponse::HTTP_OK);  // 200
        $this->assertEquals($response->original->count(), 1, '13 : Number of Tasks found invalid');
    } // 13: FilterTasksWithParentSubtasks

}

