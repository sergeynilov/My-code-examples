<?php

namespace App\Library\Services\Interfaces;

use Illuminate\Support\MessageBag;

interface DbRepositoryInterface
{
    public static function getSettingsValue(string $paramName): string;



    public static function getQuizCategory(int $quizCategoryId): array;

    public static function getEmptyQuizCategories(bool $includeInactive = null): array;

    public static function getQuizCategoriesSelections(bool $active = null): array;


    public static function getQuizzesByByIncludeInactive(bool $includeInactive = null): array;

    public static function getQuizzesWithoutIsCorrect(bool $includeInactive = null): array;

    public static function getQuizzesByQuizCategoryIds(array $quizCategoryIds, bool $active = null): array;

    public static function saveUserQuizzesHistory(array $quizCategory, array $selectedQuizAnswers, string $selectedLocale,
        int $timeSpent, int $summaryOfPoints, array $userQuizRequest): int | MessageBag;




    public static function getQuizAnswersByQuizId($quizId): array;

    public static function getQuizAnswer(int $quizAnswerId): array;

    public static function getCorrectQuizAnswer(int $quizId): array;



    public static function getUserQuizRequestByHashedLink(string $hashedLink, bool $onlyNotIsPassed): array;

    public static function storeUserQuizRequest(array $data): array | MessageBag;

    public static function getFistNotPassedUserQuizRequest(): array;



    public static function getUserQuizzesHistoriesForReview(): array;

    public static function addUserQuizRequestCommunicationChannel(int $userQuizRequestId, string $type, string $channel): int | MessageBag;

}
