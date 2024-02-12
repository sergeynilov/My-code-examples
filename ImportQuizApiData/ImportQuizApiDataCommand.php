<?php

namespace App\Console\Commands;

use App\Enums\ConfigValueEnum;
use App\Enums\QuizApiDifficulty;
use App\Exceptions\QuizApiInvalidRequest;
use App\Library\GetQuizApiData;
use App\Library\ImportQuizApiData;
use Illuminate\Console\Command;
use Str;

/**
 * Read quiz data from https://quizapi.io by provided category name using translation of Google Translate API
 *
 * and fill data into tables of the app
 *
 * @param {categoryName?} - Optional. Category name
 * @param {help?} - Optional. if set then help text is shown, but data are not read from quizapi site
 */
class ImportQuizApiDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-quiz-api-data-command  {categoryName?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read quiz data from https://quizapi.io by provided category name using translation of Google Translate API and fill data into tables of the app';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $categoryName = $this->argument('categoryName');
        if (empty($categoryName)) {
            $this->error('Category Name is not provided');

            return;
        }

        // Show Help text and exit
        if (Str::lower($categoryName) === 'help') {
            foreach ($this->getImportQuizApiHelp() as $modelHelpLine) {
                $this->info($modelHelpLine);
            };
            die('Help shown');
        }

        $newQuizCount = 0;
        $newQuizCategoryAdded = 0;
        try {
            // Read data from quizapi source
            $sourceQuizzesArray = (new GetQuizApiData)->get($categoryName, QuizApiDifficulty::Medium,
                ConfigValueEnum::get(ConfigValueEnum::GET_QUIZ_API_DATA_LIMIT));
            $importQuizApiData = new ImportQuizApiData();
            $importQuizApiData->setCategoryName($categoryName);
            $importQuizApiData->setLocales(['ua', 'es']);
            $importQuizApiData->setSourceQuizzesArray($sourceQuizzesArray);
            $importQuizApiData->setParentCommand($this); // Need to show process messages in console
            $importResult = $importQuizApiData->import();
            if ($importResult) {
                [$newQuizCount, $newQuizCategoryAdded] = $importQuizApiData->getInfo();
            }

        } catch (QuizApiInvalidRequest $e) {
            $this->error("Quiz Api importer generator error : " . $e->getMessage());
        } catch (\Exception|\Error $e) {
            $this->error($e->getMessage());
        }
        $this->info($newQuizCount . ' new quiz(zes) were added ');
        $this->info(' Check content, status and points of new ' . ($newQuizCategoryAdded ? ' quiz category and ' : '') . ' quiz(zes) ! ');
    }
    // public function handle()

    /**
     * Get model help text
     *
     * @return array
     */
    public function getImportQuizApiHelp(): array
    {
        return [
            'Fill Quizzes with data read from https://quizapi.io service by provided category name',
            'Quiz category with provided category name would be created if it does not exist',
            'Created Quiz category and quizzes would have inactive status and points equels zero',
            '',
            'Please, pay attention that Google Translate API service in many cases translates language specific key words.',
            'Please, check all added content.',
            '',
            'Param categoryName - tag at https://quizapi.io service',
            '',
            'Examples :',
            'php artisan app:import-quiz-api-data-command Linux',
            '',
            'Help text:',
            'php artisan app:import-quiz-api-data-command help',
        ];
    }

}
