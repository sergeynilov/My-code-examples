<?php

namespace App\Library;

use App\Console\Commands\ImportQuizApiDataCommand;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizCategory;
use Stichoza\GoogleTranslate\GoogleTranslate;

/**
 * Write quiz data using models QuizCategory, Quiz, QuizAnswer;
 *
 * using translation of Google Translate API for non english content
 *
 * Using of the class looks like :
 * $importQuizApiData = new ImportQuizApiData();
 * // Set Category Name
 * $importQuizApiData->setCategoryName($categoryName);
 *
 * //Locales(apart english) into which labels must be translated
 * $importQuizApiData->setLocales(['ua', 'es']);
 *
 * // data read from external service
 * $importQuizApiData->setSourceQuizzesArray($sourceQuizzesArray);
 *
 * // Parent Command - need to output console info messages. Optional
 * $importQuizApiData->setParentCommand($this);
 *
 * // Run import process
 * $importResult = $importQuizApiData->import();
 *
 * if ($importResult) {
 * //  Returns import statistics
 * [$newQuizCount, $newQuizCategoryAdded] = $importQuizApiData->getInfo();
 * }
 */
class ImportQuizApiData
{
    protected array $locales;

    protected string $categoryName;

    protected ImportQuizApiDataCommand $parentCommand;

    protected array $sourceQuizzesArray;
    protected int $newQuizCount;
    protected bool $newQuizCategoryAdded;

    /*
     * @param ?? $value - Parent Command - need to output console info messages. Optional
     *
     * @return self
     * */
    public function setParentCommand(ImportQuizApiDataCommand $value): self
    {
        $this->parentCommand = $value;

        return $this;
    }

    /*
     * @param string $value - name of target Quiz Category
     *
     * @return self
     * */
    public function setCategoryName(string $value): self
    {
        $this->categoryName = $value;

        return $this;
    }

    /*
     * @param array $value - additive locales(apart english) into which labels must be translated
     *
     * @return self
     * */
    public function setLocales(array $value): self
    {
        $this->locales = $value;

        return $this;
    }

    /*
     * @param array $value - data read from external service
     *
     * @return self
     * */
    public function setSourceQuizzesArray(array $value): self
    {
        $this->sourceQuizzesArray = $value;

        return $this;
    }

    /*
     * Run import process
     *
     * @return bool
     * */
    public function import(): bool
    {
        $this->newQuizCategoryAdded = false;
        $quizCategoryNameArray = [];
        $googleTranslates = [];
        foreach ($this->locales as $locale) { // Prepare all GoogleTranslate objects for all locales except default(english)
            // Fill default english version for quiz category
            $quizCategoryNameArray[AppLocale::getDefaultLocale()] = $this->categoryName;
            if ($locale != AppLocale::getDefaultLocale()) {
                $googleTranslates[$locale] = new GoogleTranslate($locale === 'ua' ? 'uk' : $locale); // Ukraine domain issue
                $googleTranslates[$locale]->setSource(AppLocale::getDefaultLocale()); // Translate from English
                $quizCategoryNameArray[$locale] = $googleTranslates[$locale]->translate($this->categoryName);
            }
        }
        $quizCategory = QuizCategory::whereJsonContains('name', $quizCategoryNameArray)->first();
        if (empty($quizCategory)) { // Create a new QuizCategory by $categoryName if it does not exist yet
            $quizCategory = new QuizCategory();
            $quizCategory->name = $quizCategoryNameArray;
            $quizCategory->active = false;
            $quizCategory->save();
            $this->newQuizCategoryAdded = true;
        }

        $quizzesQuestionArray = [];
        $quizzesAnswers = [];
        $currentRow = 0;
        $this->newQuizCount = 0;
        foreach ($this->sourceQuizzesArray as $sourceQuiz) {  // Loop all quizzes
            // Fill default english version for quiz questions
            $quizzesQuestionArray[AppLocale::getDefaultLocale()] = $sourceQuiz->question;
            foreach ($googleTranslates as $locale => $googleTranslate) { // through all GoogleTranslate objects =- to translate all locales of the app
                $quizzesQuestionArray[$locale] = $googleTranslate->translate($sourceQuiz->question);
            }


            $quiz = Quiz::whereJsonContains('question', $quizzesQuestionArray)->first();
            if (empty($quiz)) { // Create a new $quiz by question if it does not exist yet
                $quiz = new Quiz();
                $quiz->question = $quizzesQuestionArray;
                $quiz->points = 0;
                $quiz->quiz_category_id = $quizCategory->id;
                $quiz->active = false;
                $quiz->save();
                $this->newQuizCount++;
            }

            $quizAnswers = (array)$sourceQuiz->answers;
            $correctAnswers = (array)$sourceQuiz->correct_answers;
            foreach ($quizAnswers as $key => $answer) { // Fill answers array with correct answer
                if ( ! empty($answer)) {
                    $quizzesAnswers[] = ['answer' => $answer, 'correct' => $correctAnswers[$key . '_correct'] === "true"];
                }
            }

            foreach ($quizzesAnswers as $quizzesAnswerData) {
                $quizzesAnswer = new QuizAnswer();
                // Fill default english version for quiz answer
                $quizzesAnswerTextArray[AppLocale::getDefaultLocale()] = $quizzesAnswerData['answer']; // fill english version

                foreach ($googleTranslates as $locale => $googleTranslate) { // through all GoogleTranslate objects
                    $translatedText = $googleTranslate->translate($quizzesAnswerData['answer']);
                    $quizzesAnswerTextArray[$locale] = $translatedText;
                }

                $quizzesAnswer->text = $quizzesAnswerTextArray;
                $quizzesAnswer->quiz_id = $quiz->id;
                $quizzesAnswer->is_correct = $quizzesAnswerData['correct'];
                $quizzesAnswer->save();
            }
            $currentRow++;
            if ( ! empty($this->parentCommand)) {
                $this->parentCommand->info($currentRow . ' / ' . count($this->sourceQuizzesArray) . ' - ' . (100 / count($this->sourceQuizzesArray) * $currentRow) . '% ');
            }
        }

        // foreach ($sourceQuizzesArray as $quiz) {  // Loop all quizzes

        return true;
    }

    /*
     * Returns import statistics
     *
     * @return array of new quiz count and new quiz category added
     * */
    public function getInfo(): array
    {
        return [$this->newQuizCount, $this->newQuizCategoryAdded];
    }
}

