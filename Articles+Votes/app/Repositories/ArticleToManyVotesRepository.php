<?php

namespace App\Repositories;

use App\Models\{Article, Vote, User};

use App\Repositories\Interfaces\ManyToManyItemsInterface;
use App;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;
use App\Http\Resources\ArticleToManyVotesResource;

use App\Http\Resources\VoteResource;

class ArticleToManyVotesRepository implements ManyToManyItemsInterface
{
    /**
     * Returns collection of Votes assigned to Articles by $id(articleId)
     *
     * @param int $id - key $articleId for filtering
     *
     * @return $articleVotes -  collection of found Article Votes data
     *
     */
    public function getToManyItems(int $id): array
    {
        $article      = Article::getById($id)
            ->firstOrFail();
        $articleVotes = $article->onlyActiveVotes;
        $articleVotes->load('creator')->load('voteCategory');

        return [
            'articleVotes' => ArticleToManyVotesResource::collection($articleVotes),
        ];
    }

    /**
     * Returns collection of Votes assigned to Articles by $id(articleId) with provided filters
     *
     * @param int $id - key $articleId for filtering
     *
     * @param array $request- >filter_vote_vote_category_id/ filter_vote_name / filter_vote_status - additive filters
     *
     * @return $filteredVotes -  collection of found Article Votes data
     *
     */
    public function getToManyFilteredItems(int $id): array
    {
        $request = request();
        $article = Article::getById($id)
            ->firstOrFail();

        $voteTableName = ((new Vote)->getTable());
        $filteredVotes = $article->onlyActiveVotes()
            ->where($voteTableName . '.vote_category_id', $request->filter_vote_vote_category_id)
            ->where($voteTableName . '.name', 'like', '%' . $request->filter_vote_name . '%')
            ->where($voteTableName . '.status', $request->filter_vote_status)
            ->get();

        return [
            'filteredVotes' => VoteResource::collection($filteredVotes),
        ];
    }

    /**
     * Assign to existing Article($id) model Vote($manyItemId) model with many-to-many relation
     *
     * @$data - pivot table data
     *
     * @return \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag
     *
     * @return Model
     */
    public function storeToManyItem(
        int $id,
        int $manyItemId,
        array $data,
        bool $makeValidation = false
    ): JsonResponse|MessageBag
    {
        if (empty($data['article_id'])) {
            $data['article_id'] = $id;
        }
        if (empty($data['vote_id'])) {
            $data['vote_id'] = $manyItemId;
        }
        if ($makeValidation) {
            $articleVoteValidationRulesArray = [
                'active'           => 'nullable|boolean',
                'expired_at'       => 'nullable|date',
                'supervisor_id'    => 'required|exists:' . ((new User)->getTable()) . ',id',
                'supervisor_notes' => 'nullable|string',

            ];
            $validator = \Illuminate\Support\Facades\Validator::make(
                $data,
                $articleVoteValidationRulesArray
            );
            if ($validator->fails()) {
                $errorMsg = $validator->getMessageBag();
                return response()->json(['errors' => $errorMsg], HTTP_RESPONSE_BAD_REQUEST); // 400
            }
        } // if ($makeValidation) {

        $article = Article::findOrFail($id);
        $vote    = Vote::findOrFail($manyItemId);

        if ($article->votes()->where('vote_id', $manyItemId)->exists()) {
            return response()->json(
                ['message' => 'Article "' . $id . '" with vote ' . $manyItemId . ' already exists'],
                HTTP_RESPONSE_BAD_REQUEST
            ); // 400
        }

        DB::beginTransaction();
        try {
            $article->votes()->attach($manyItemId, $data);
            DB::Commit();

        } catch (\Exception $e) {
            DB::rollback();

            return \App\Library\AppCustomException::getInstance()::raiseChannelError(
                errorMsg: $e->getMessage(),
                exceptionClass: \Exception::class,
                file: __FILE__,
                line: __LINE__
            );
        }

        return response()->json(['result' => true], HTTP_RESPONSE_OK_RESOURCE_CREATED); // 201
    }


    /**
     * Update related vote($manyItemId) of  Article($id) model in storage
     *
     * @$data - pivot table data
     * @return \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag
     */
    public function updateManyItems(
        int $id,
        int $manyItemId,
        array $data,
        bool $makeValidation = false
    ): JsonResponse|MessageBag {
        if (empty($data['article_id'])) {
            $data['article_id'] = $id;
        }
        if (empty($data['vote_id'])) {
            $data['vote_id'] = $manyItemId;
        }
        if ($makeValidation) {
            $articleVoteValidationRulesArray = [
                'active'           => 'nullable|boolean',
                'expired_at'       => 'nullable|date',
                'supervisor_id'    => 'required|exists:' . ((new User)->getTable()) . ',id',
                'supervisor_notes' => 'nullable|string',

            ];
            $validator = \Illuminate\Support\Facades\Validator::make(
                $data,
                $articleVoteValidationRulesArray
            );
            if ($validator->fails()) {
                $errorMsg = $validator->getMessageBag();

                return response()->json(['errors' => $errorMsg], HTTP_RESPONSE_BAD_REQUEST); // 400
            }
        } // if ($makeValidation) {

        $article = Article::findOrFail($id);
        $vote    = Vote::findOrFail($manyItemId);

        DB::beginTransaction();
        try {
            if ( ! $article->votes()->where('vote_id', $manyItemId)->exists()) {
                $article->votes()->attach($manyItemId, $data);
            } else {
                $vote->articles()->updateExistingPivot($id, $data);
            }
            DB::Commit();
        } catch
        (\Exception $e) {
            DB::rollback();

            return \App\Library\AppCustomException::getInstance()::raiseChannelError(
                errorMsg: $e->getMessage(),
                exceptionClass: \Exception::class,
                file: __FILE__,
                line: __LINE__
            );
        }

        $articleVotes = $article->onlyActiveVotes;

        return response()->json(
            ['articleVotes' => ArticleToManyVotesResource::collection($articleVotes)],
            HTTP_RESPONSE_OK_RESOURCE_UPDATED
        ); // 205
    }

    /**
     * Remove the specified existing Article($id) model from related Vote($manyItemId) model with many-to-many relation
     *
     * @return \Illuminate\Support\MessageBag|void
     */
    public function deleteToManyItem(int $id, int $manyItemId): \Illuminate\Http\Response
    {
        $article = Article::findOrFail($id);

        DB::beginTransaction();
        try {
            $article->votes()->detach($manyItemId);
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();

            return \App\Library\AppCustomException::getInstance()::raiseChannelError(
                errorMsg: $e->getMessage(),
                exceptionClass: \Exception::class,
                file: __FILE__,
                line: __LINE__
            );
        }

        return response()->noContent();
    }


}
