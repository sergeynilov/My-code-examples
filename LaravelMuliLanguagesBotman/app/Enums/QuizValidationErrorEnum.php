<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class QuizValidationErrorEnum extends Enum
{
    const QVE_EMPTY_QUIZ_CATEGORIES = 'EmptyQuizCategories';
    const QVE_EMPTY_QUIZ_CATEGORIES_IDS = 'EmptyQuizCategoriesIds';
    const QVE_HAS_MORE_ONE_IS_CORRECT_QUIZ_ANSWERS = 'HasMoreOneIsCorrectQuizAnswers';
    const QVE_MORE_ONE_IS_CORRECT_QUIZ_ANSWERS_IDS = 'MoreOneIsCorrectQuizAnswersIds';

    const QVE_HAS_NO_IS_CORRECT_QUIZ_ANSWERS = 'HasNoIsCorrectQuizAnswers';
    const QVE_HAS_NO_IS_CORRECT_QUIZ_ANSWERS_IDS = 'HasNoIsCorrectQuizAnswersIds';

    const QVE_QUIZZES_WITH_EMPTY_LOCALES_COUNT = 'QuizzesWithEmptyLocalesCount';
    const QVE_QUIZZES_WITH_EMPTY_LOCALES_IDS = 'QuizzesWithEmptyLocalesIds';
}
