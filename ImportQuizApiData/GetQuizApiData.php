<?php

namespace App\Library;


use App\Enums\QuizApiDifficulty;
use App\Exceptions\QuizApiInvalidRequest;
use Illuminate\Support\Facades\Http;

class GetQuizApiData
{
    /**
     * Read data from quizapi.io service
     *
     * @param string $categoryText - tag name
     *
     * @param QuizApiDifficulty $quizApiDifficulty - can be Easy, Medium, Hard
     *
     * @return self
     *
     */
    public function get(string $categoryText, QuizApiDifficulty $quizApiDifficulty, int $limit = 10): array
    {
        $quizApiToken = config('app.quiz_api_token');
        $response = Http::get('https://quizapi.io/api/v1/questions', [
            'apiKey' => $quizApiToken,
            'tags' => $categoryText,
            'limit' => $limit,
            'difficulty' => $quizApiDifficulty->value
        ]);

        if ($response->getStatusCode() != 200) {
            $errorMessage = json_decode($response)->error ?? '';
            throw new QuizApiInvalidRequest('Request category "' . $categoryText . '" : ' . $errorMessage . ', with status code ' . $response->getStatusCode());
        }
        $data = json_decode($response->getBody());

        return $data;
    }
}
