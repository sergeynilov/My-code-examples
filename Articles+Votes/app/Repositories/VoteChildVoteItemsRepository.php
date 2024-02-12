<?php

namespace App\Repositories;

use App\Models\Vote;
use App\Models\Taggable;

use App\Repositories\Interfaces\ChildItemsInterface;
use App;
use App\Models\VoteItem;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\MessageBag;
use App\Http\Resources\VoteItemResource;

class VoteChildVoteItemsRepository implements ChildItemsInterface
{
    /**
     * Returns collection of Taggable by $parentId($voteId)
     *
     * @param int $parentId - key $voteId for filtering
     *
     * @return array : voteItems -voteItems collection of found VoteItem models
     *
     */
    public function getChildItems(int $parentId): array
    {
        $voteItems = VoteItem::getByVoteId($parentId)
            ->orderBy('ordering', 'asc')
            ->get();

        return [
            'voteItems' => VoteItemResource::collection($voteItems),
        ];
    }

    /**
     * Store new validated Vote Item model in storage
     *
     * @param int $parentId - key $voteId for filtering, $data - fields for VoteItem item , bool $makeValidation - if
     * data must be validated before updating
     *
     * @return \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag
     */
    public function storeChildItem(int $parentId, array $data, bool $makeValidation = false): JsonResponse|MessageBag
    {
        if ($makeValidation) {
            /* Validate VoteItem by rules in model's method  */
            $voteItemValidationRulesArray = VoteItem::getValidationRulesArray(
                voteId: $parentId,
                voteItemId: null
            );
            $validator = \Illuminate\Support\Facades\Validator::make(
                $data,
                $voteItemValidationRulesArray,
                VoteItem::getValidationMessagesArray()
            );
            if ($validator->fails()) {
                $errorMsg = $validator->getMessageBag();

                return response()->json(['message' => $errorMsg], HTTP_RESPONSE_BAD_REQUEST); // 400
            }
        } // if ($makeValidation) {

        DB::beginTransaction();
        try {

            if ($data['is_correct']) { // new Vote Item is marked as is_correct = true - so rest of Vote Item must be set is_correct = false
                VoteItem::getByVoteId($parentId)
                    ->getByIsCorrect(true)
                    ->get()
                    ->map(function ($voteItem) {
                        $voteItem->is_correct = false;
                        $voteItem->updated_at = Carbon::now(config('app.timezone'));
                        $voteItem->save();

                        return $voteItem;
                    });

            }
            $voteItem = VoteItem::create([
                'name'       => $data['name'],
                'vote_id'    => $parentId,
                'is_correct' => $data['is_correct'] ?? false,
                'ordering'   => ! empty($data['ordering']) ? $data['ordering'] : VoteItem::max('ordering') + 1,
            ]);
            DB::Commit();

            $voteItem->load('media');

            return response()->json(['vote_item' => (new VoteItemResource($voteItem))],
                HTTP_RESPONSE_OK_RESOURCE_CREATED); // 201
        } catch (\Exception $e) {
            DB::rollback();

            return \App\Library\AppCustomException::getInstance()::raiseChannelError(
                errorMsg: $e->getMessage(),
                exceptionClass: \Exception::class,
                file: __FILE__,
                line: __LINE__
            );
        }
    }

    /**
     * Update existing validated Vote Item model in storage
     *
     * @param int $parentId - key $voteId for filtering, $itemId - id of updated VoteItem model,
     * $data - fields for VoteItem item , bool $makeValidation - if data must be validated before updating
     *
     * @return \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag
     */
    public function UpdateChildItem(
        int $parentId,
        int $itemId,
        array $data,
        bool $makeValidation = false
    ): JsonResponse|MessageBag {
        try {
            $voteItem = VoteItem::findOrFail($itemId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Vote item"' . $itemId . '" not found.'],
                HTTP_RESPONSE_NOT_FOUND); // 404
        }

        if ($makeValidation) {
            /* Validate VoteItem by rules in model's method  */
            $voteItemValidationRulesArray = VoteItem::getValidationRulesArray(
                voteId: $parentId,
                voteItemId: $itemId
            );
            $validator = \Illuminate\Support\Facades\Validator::make(
                $data,
                $voteItemValidationRulesArray,
                VoteItem::getValidationMessagesArray()
            );
            if ($validator->fails()) {
                $errorMsg = $validator->getMessageBag();

                return response()->json(['message' => $errorMsg], HTTP_RESPONSE_BAD_REQUEST); // 400
            }
        } // if ($makeValidation) {

        DB::beginTransaction();
        try {

            if ($data['is_correct']) { // new Vote Item is marked as is_correct = true - so rest of Vote Item must be set is_correct = false
                VoteItem::getByVoteId($parentId)
                    ->where('id', '!=', $itemId)
                    ->getByIsCorrect(true)
                    ->get()
                    ->map(function ($voteItem) {
                        $voteItem->is_correct = false;
                        $voteItem->updated_at = Carbon::now(config('app.timezone'));
                        $voteItem->save();

                        return $voteItem;
                    });
            }
            $voteItem->update($data);
            DB::Commit();

            $voteItem->load('media');
            return response()->json(['vote_item' => (new VoteItemResource($voteItem))],
                HTTP_RESPONSE_OK_RESOURCE_UPDATED); // 205
        } catch (\Exception $e) {
            DB::rollback();

            return \App\Library\AppCustomException::getInstance()::raiseChannelError(
                errorMsg: $e->getMessage(),
                exceptionClass: \Exception::class,
                file: __FILE__,
                line: __LINE__
            );
        }
    }


    /**
     * Remove existing Vote Item model from storage
     *
     * @param int $parentId - key $voteId for filtering, $itemId - id of deleted VoteItem model
     * @return \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag
     */
    public function deleteChildItem(int $parentId, int $itemId): Response|JsonResponse|MessageBag
    {
        try {
            $voteItem = VoteItem::findOrFail($itemId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Vote item"' . $itemId . '" not found.'],
                HTTP_RESPONSE_NOT_FOUND); // 404
        }

        DB::beginTransaction();
        try {
            $voteItem->delete();
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
