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

class ReportsTest extends TestCase
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
    public function test_1_ShowTaskDetailsReport()
    {
        // Test Data Setup
        $task = Task::factory()->userId(self::$loggedUser->id)->create();

        // Test Action
        $task->id = 8 ; // TODO
        $response = $this
            ->actingAs(self::$loggedUser, 'sanctum')
            ->postJson(route('reports.showTaskDetailsReport', [
                'taskId' => $task->id,
            ]));

        // Check Assert
        $response->assertStatus(JsonResponse::HTTP_OK);  // 200
    } // 1: ShowTaskDetailsReport
}
