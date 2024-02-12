<?php

namespace App\Library;

use App;
use App\Library\Services\Interfaces\DbRepositoryInterface;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer as BotManAnswer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question as BotManQuestion;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Spatie\Emoji\Emoji;

class QuizzesWizard extends Conversation
{
    /** @var int - value from url for filtering of active Quizzes */
    protected int $quizCategoryId;

    /** @var UserQuizRequest model/array - current user quiz request on which these quizzes are set */
    protected array $currentUserQuizRequest;

    /** @var QuizCategory - model/array - current quiz category, which quizzes are shown */
    protected array $currentQuizCategory;

    /** @var Quiz/array Collection of active Quizzes filtered by currentQuizCategory */
    protected array $activeQuizzes;

    /** @var array - which quiz answers were selected by user */
    protected array $selectedQuizAnswers = [];

    /** @var int summary of points awarded by user */
    protected int $summaryOfPoints = 0;

    /** @var int - number of correct answers made by user */
    protected $correctAnswersSelectedCount = 0;

    /** @var int */
    protected int $activeQuizzesCount = 0; // we already had this one

    /** @var int */
    protected int $currentQuizAnswer = 1;

    /** @var string - logo image of the app */
    protected string $logoImage = '/images/communication_channel.jpeg';

    /** @var string - results of quiz image */
    protected string $resultsOfQuizImage = '/images/results_of_quiz.jpeg';

    /** @var int - time when user started the quiz - to calculated time spent on all quizzes */
    protected int $timeWizardStarted = 0;

    /* @var bool - if to show correct quiz answer if use made wrong choice */
    protected bool $showCorrectQuizAnswerOnWrongAnswer = false;

    /* @var bool - if to show widget for user to enter preferable_communication_channel */
    protected bool $showAskPreferableCommunicationChannel = false;

    /* @var bool - if to show on user's answer if it was correct/wrong answer */
    protected bool $showFeedbackOnCorrectWrongAnswer = false;

    /* @var bool if to show final results for use when Quiz is completed */
    protected bool $showFinalQuizResults = false;

    /* @var DbRepositoryInterface implementation of DbRepositoryInterface provides all storage methods for data retrieving/saving */
    protected DbRepositoryInterface $dbRepositoryServiceInterface;

    public function __construct()
    {
        $this->dbRepositoryServiceInterface = App::make(DbRepositoryInterface::class);
    }


    /**
     * Start the wizard with reading data from storage and asking current locale.
     *
     * @return void
     */
    public function run(): void
    {
        $this->askCurrentLocale();
    }

    /**
     * Asking/setting current locale from languages supported by app(both in spatie fields and lang/LOCALE/botman.php files).
     *
     * @return void
     */
    protected function askCurrentLocale(): void
    {
        $currentLocaleButtons = [];
        // Create languages of the app listing
        $currentLocale = $this->bot->userStorage()->find('currentLocale');
        $appLocales = AppLocale::getInstance($currentLocale)->getAppLocaleSelectionItems(false);
        foreach ($appLocales as $key => $label) {
            // as Country flag in Emoji can be different from locale - need find and uset  it
            $countryFlag = AppLocale::getInstance($currentLocale)->getLocaleCountryFlag($key);
            $currentLocaleButtons[] = Button::create(Emoji::countryFlag($countryFlag) . ' ' . $label)->value($key);
        }

        $question = Question::create(__('botman.Select language'))
            ->callbackId('communication_channel_id')
            ->addButtons($currentLocaleButtons);

        $this->ask($question, function (Answer $currentLocaleAnswer) use ($appLocales) {
            // Keep selected current locale
            $this->initCurrentLocale($currentLocaleAnswer->getValue());

            $this->startQuizzesWizard();
        });
    }

    /* Show quizzes wizard with greeting and first quiz/step shown
     *
     * @return void
     */
    protected function startQuizzesWizard(): void
    {
        $currentLocale = $this->setCurrentLocale();
        $this->currentQuizCategory = $this->dbRepositoryServiceInterface::getQuizCategory($this->quizCategoryId);
        $this->activeQuizzes       = $this->dbRepositoryServiceInterface::getQuizzesByQuizCategoryIds([$this->quizCategoryId],
            true);
        $this->activeQuizzesCount  = count($this->activeQuizzes);
        $this->showMessageWithImage(
            message: __('botman.welcome') . ' ' . $this->currentUserQuizRequest['user_name'] . ' ' . __('botman.on our quiz'),
            image: $this->logoImage
        );

        $this->showMessageWithImage(
            message: __('botman.You have selected :localeLabel for this quiz',
            ['localeLabel' => AppLocale::getInstance($currentLocale)->getAppLocaleLabel($currentLocale)]),
            image: AppLocale::getInstance($currentLocale)->getCurrentLocaleImageUrl()
        );

        $this->say(__('botman.You have to answer to :quizzesCount questions',
                ['quizzesCount' => $this->activeQuizzesCount]) . ' ' .
                   __('botman.in') . __('botman.category') . ' "' . $this->currentQuizCategory['locale_name'] . '" ' . '. ' .
                   __('botman.On any correct answer you will get some number of points') . ' '. __('botman.Please do not waste too much of time'));
        $this->timeWizardStarted = microtime(true);
        $this->showNextQuizBlock();
    }


    /*
    When user selected some option from proposed quiz the next quiz block is opened
     */
    protected function showNextQuizBlock()
    {
        if (count($this->activeQuizzes) > 0) {
            $this->askNextQuizBlock($this->activeQuizzes[0]);
        } else {
            // When user passed through all quizzes - show result messages info
            $this->showCompletedQuizResults();
        }
    }

    /*
    Fill content of next quiz block based on answers selectedQuiz
     */
    protected function askNextQuizBlock(array $quiz)
    {
        $this->ask($this->createQuizWithAnswersBlock($quiz), function (BotManAnswer $quizAnswer) use ($quiz) {
            $this->setCurrentLocale();
            $quizAnswer = $this->dbRepositoryServiceInterface::getQuizAnswer((int)$quizAnswer->getValue());

            if ( ! $quizAnswer) {
                $this->say(__('botman.Invalid quiz answer selected'));
                $this->showNextQuizBlock();
            }
            foreach ($this->activeQuizzes as $key => $activeQuiz) {
                // To remove current quiz from active quizzes
                if ($activeQuiz['id'] === $quiz['id']) {
                    unset($this->activeQuizzes[$key]);
                    shuffle($this->activeQuizzes);
                }
            }

            // save which quiz answer was selected by user
            $this->selectedQuizAnswers[] = [
                'quiz_id'        => $quiz['id'],
                'quiz_answer_id' => $quizAnswer['id'],
                'is_correct'     => $quizAnswer['is_correct'],
            ];
            if ($quizAnswer['is_correct']) { // on correct answer add points
                $this->summaryOfPoints += $quiz['points'];
                $this->correctAnswersSelectedCount++;
                $answerResultText = $this->showFeedbackOnCorrectWrongAnswer ? __('botman.Your answer is correct') . ' ' . Emoji::CHARACTER_THUMBS_UP/*' ✅'*/ : '';
            } else {
                $answerResultText = '';
                if ($this->showFeedbackOnCorrectWrongAnswer) {
                    $answerResultText = __('botman.Your answer is incorrect') . '. ' . Emoji::CHARACTER_THUMBS_DOWN . ' ' . $this->getCorrectQuizAnswer($quiz);
                }
            }
            $this->currentQuizAnswer++;

            $this->say(__('botman.Your answer ":answer" accepted',
                    ['answer' => $quizAnswer['locale_text']]) . ' ' . $answerResultText);
            $this->showNextQuizBlock();
        });
    }

    /*
       returns correct quiz answer text if user selected invalid answer
    */
    protected function getCorrectQuizAnswer(array $quiz): string
    {
        if ($this->showCorrectQuizAnswerOnWrongAnswer) {
            $correctQuizAnswer = $this->dbRepositoryServiceInterface::getCorrectQuizAnswer($quiz['id']);
        }

        return ! empty($correctQuizAnswer) ? ' ' . __('botman.Correct answer is ":correctAnswer"',
                ['correctAnswer' => $correctQuizAnswer['locale_text']]) : '';
    }

    /*
        Generate block with Quiz text with answers - user must select one of them
    */
    protected function createQuizWithAnswersBlock(array $quiz)
    {
        $quizText              = Emoji::CHARACTER_DEPARTMENT_STORE . '  ' . __('botman.Quiz') . ': ' . $this->currentQuizAnswer .
                                 ' / ' . $this->activeQuizzesCount . ' : ' . $quiz['locale_question'];
        $quizQuestionsTemplate = BotManQuestion::create($quizText);
        $this->quizAnswers     = $this->dbRepositoryServiceInterface::getQuizAnswersByQuizId($quiz['id']);

        foreach ($this->quizAnswers as $quizAnswer) {
            $quizQuestionsTemplate->addButton(Button::create($quizAnswer['locale_text'])->value($quizAnswer['id']));
        }

        return $quizQuestionsTemplate;
    }

    /*
     *
        When user passed through all quizzes blocks save user quizzes history and show results info
    */
    protected function showCompletedQuizResults(): void
    {
        $this->setCurrentLocale();
        $this->say(__('botman.You have finished the quizzes'));

        $now = Carbon::parse(microtime(true));
        $storageData = $this->bot->userStorage()->find();
        $currentLocale = $storageData->get('currentLocale');
        $ret = $this->dbRepositoryServiceInterface::saveUserQuizzesHistory(
            quizCategory: $this->currentQuizCategory,
            selectedQuizAnswers: $this->selectedQuizAnswers,
            selectedLocale: $currentLocale,
            timeSpent: $now->diffInSeconds(Carbon::parse($this->timeWizardStarted)),
            summaryOfPoints: $this->summaryOfPoints,
            userQuizRequest: $this->currentUserQuizRequest
        );

        if ($this->showFinalQuizResults) {
            $this->setCurrentLocale();
            $this->say(__('botman.You answered all the quizzes') . ' ' . Emoji::CHARACTER_RAISED_HAND . ' ' .
                       __('botman.You reached :summaryOfPoints points', ['summaryOfPoints' => $this->summaryOfPoints]) . ' ' .
                       __('botman.Correct answers :correctAnswersSelectedCount of :activeQuizzesCount', [
                           'correctAnswersSelectedCount' => $this->correctAnswersSelectedCount,
                           'activeQuizzesCount'          => $this->activeQuizzesCount
                       ]) . ' ' .
                       __('botman.You spent :timeSpentLabel on this quiz',
                           ['timeSpentLabel' => $this->localeTimeLabels(timeSpentLabel($this->timeWizardStarted, $now, 'before'))])
            );
        }

        if ($this->showAskPreferableCommunicationChannel) {
            $this->askPreferableCommunicationChannel();
        }
    }

    /*
    Show message with image based on @vars string $message and string $image url(relative to the site root)
     *
     * @return void
     */
    protected function showMessageWithImage(string $message, string $image): void
    {
        $message = OutgoingMessage::create($message)
            ->withAttachment(Image::url($image));
        $this->bot->reply($message);
    }

    /**
     *  Set user quiz request with user's info and quizzes category for selection
     *
     * @return void
     */
    public function setUserQuizRequest(array $value): void
    {
        $this->currentUserQuizRequest = $value;
        $this->quizCategoryId         = $this->currentUserQuizRequest['quiz_category_id'];
    }

    public function setShowCorrectQuizAnswerOnWrongAnswer(bool $value): void
    {
        $this->showCorrectQuizAnswerOnWrongAnswer = $value;
    }

    public function setShowAskPreferableCommunicationChannel(bool $value): void
    {
        $this->showAskPreferableCommunicationChannel = $value;
    }

    public function setShowFeedbackOnCorrectWrongAnswer(bool $value): void
    {
        $this->showFeedbackOnCorrectWrongAnswer = $value;
    }

    public function setShowFinalQuizResults(bool $value): void
    {
        $this->showFinalQuizResults = $value;
    }

    /*
    When used completed quizzes he/she can add preferable communication channel to get in touch
     * */
    protected function askPreferableCommunicationChannel(): void
    {
        $communicationChannels       = config('botman.web.communicationChannels');
        $communicationChannelButtons = [];
        // Create preferable communication channels listing
        foreach ($communicationChannels as $key => $label) {
            $communicationChannelButtons[] = Button::create(__('botman.' . $label))->value($key);
        }

        $communicationChannelButtons[] = Button::create(__('botman.No preferable channel'))->value('no_preferable_channel');
        $this->say($this->currentUserQuizRequest['user_name'] . ', ' . __('botman.You provided your email for contact') . $this->currentUserQuizRequest['user_email']);

        $question = Question::create(__('botman.Or select preferable communication channel'))
            ->callbackId('preferable_communication_channel')
            ->addButtons($communicationChannelButtons);
        $this->ask($question, function (Answer $communicationChannelAnswer) use ($communicationChannels) {

            $this->setCurrentLocale();
            if ($communicationChannelAnswer->isInteractiveMessageReply() and $communicationChannelAnswer->getValue() !== 'no_preferable_channel') {
                // Need to save selected communication channel
                $channelType = $communicationChannelAnswer->getValue();
                $channelName = $communicationChannels[$communicationChannelAnswer->getValue()];

                $this->setCurrentLocale();
                $this->ask(Emoji::CHARACTER_CALL_ME_HAND . ' ' . __('botman.Enter your :channel',
                        ['channel' => __('botman.' . $channelName)]) . '. ' . __('botman.Your :channelType will be saved and used for communication',
                        ['channelType' => __('botman.' . $communicationChannels[$communicationChannelAnswer->getValue()])]),
                    function (Answer $communicationChannelAnswer) use ($channelType) {
                        $this->dbRepositoryServiceInterface::addUserQuizRequestCommunicationChannel(
                            $this->currentUserQuizRequest['id'], $channelType, $communicationChannelAnswer->getText()
                        );

                        $this->setCurrentLocale();
                        $this->say(
                            __('botman.Congratulations') . ' ' .
                            __('botman.You completed the quiz') . ' ' .
                            __('botman.We will get in touch with you'));
                    });
            } else {
//                $this->setCurrentLocale();
                $this->say(
                    __('botman.Congratulations') . ' ' .
                    __('botman.You completed the quiz') . ' ' .
                    __('botman.We will get in touch with you'));
            }

        });
    }

    protected function setCurrentLocale(): string
    {
        $storageData = $this->bot->userStorage()->find();
        $currentLocale = $storageData->get('currentLocale');

        $this->dbRepositoryServiceInterface::setCurrentLocale($currentLocale);
        app()->setLocale($currentLocale);
        return $currentLocale;
    }

    protected function initCurrentLocale(string $currentLocale): void
    {
        $this->bot->userStorage()->save([
            'currentLocale' => $currentLocale
        ]);
        app()->setLocale($currentLocale);
    }

    protected function localeTimeLabels(string $value): string
    {
        $value = Str::replace('minutes', __('botman.minutes'), $value);
        $value = Str::replace('minute', __('botman.minute'), $value);
        $value = Str::replace('seconds', __('botman.seconds'), $value);
        $value = Str::replace('second', __('botman.second'), $value);
        $value = Str::replace('hours', __('botman.hours'), $value);
        $value = Str::replace('hour', __('botman.hour'), $value);
        $value = Str::replace('days', __('botman.days'), $value);
        $value = Str::replace('day', __('botman.day'), $value);
        return $value;
    }

}
