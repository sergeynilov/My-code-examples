<?php

namespace App\Repositories;

use Illuminate\Support\MessageBag;
use App\Models\Vote;
use App\Enums\UserAccountManagerEnum;
use App\Library\Services\Interfaces\UserAccountManager;
use App\Repositories\Interfaces\CrudInterface;
use App\Repositories\Interfaces\UploadedImageInterface;
use App\Repositories\Interfaces\ItemStatusModificationInterface;
use App\Library\Services\Interfaces\UploadedFileManagementInterface;

use App\Http\Resources\VoteResource;
use App\Settings\AppGeneralSettings;
use Carbon\Carbon;
use DateConv;
use DB;
use Auth;
use App\Enums\UpdateVoteRules;
use App\Library\Rules\RulesVoteUpdate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

use App\Exceptions\UploadedImageHasInvalidProps;

/*
*
Repository Class for Vote models CRUD methods.
Its methods are called from app/Http/Controllers/VoteController.php
*
* implements several Interfaces
*/

class VoteCrudRepository implements CrudInterface, ItemStatusModificationInterface, UploadedImageInterface
{
    private $appGeneralSettings;
    private $userAccountManager;

    public function __construct(AppGeneralSettings $appGeneralSettings, UserAccountManager $userAccountManager)
    {
        $this->appGeneralSettings = $appGeneralSettings;
        $this->userAccountManager = $userAccountManager;
    }

    /*  Vote CRUD(implements CrudInterface) BLOCK START */

    /**
     * Returns (filtered) paginated collection of votes
     *
     * @param string $page - paginated page, if empty - all data would be returned
     *
     * @param string $sortedBy - how data are sorted, can be combination of fields
     *
     * @param string $filters - how data are filtered, keys : status - enum, name - string, is_quiz - boolean,
     * is_homepage - boolean, vote_category_id - integer(reference to VoteCategory model)
     *
     * returns a filtered data / total rows number / pagination per page
     *
     * @return array : Votes - collection of found data,
     * totalVotesCount - total number of found votes,
     * paginationPerPage - number of rows in 1 paginated page
     */
    public function filter(int $page = 1, string $sortedBy = '', array $filters = []): array
    {
        /*
        * Checks if current logged user has access to this method by user's state
        */
        $this->userAccountManager->checkPermissions(
            [
                UserAccountManagerEnum::UAM_USER_HAS_ACTIVE_STATUS,
                UserAccountManagerEnum::UAM_USER_IS_DEVELOPERS_TEAM,
                UserAccountManagerEnum::UAM_USER_HAS_NO_BAN,
                UserAccountManagerEnum::UAM_USER_HAS_VOTES_ACCESS,
            ],
            "filter"
        );

        $paginationPerPage = $this->appGeneralSettings->items_per_page;

        $filterStatus         = $filters['status'] ?? '';
        $filterName           = $filters['name'] ?? '';
        $filterIsQuiz         = $filters['is_quiz'] ?? null;
        $filterIsHomepage     = $filters['is_homepage'] ?? null;
        $filterVoteCategoryId = $filters['vote_category_id'] ?? null;

        /* Set sorting by request parameters */
        $sortByField          = 'status';
        $sortOrdering         = 'desc';
        $additiveSortByField  = 'name';
        $additiveSortOrdering = 'asc';
        if ($sortedBy == 'status_name') {
            $sortByField          = 'status';
            $sortOrdering         = 'desc';
            $additiveSortByField  = 'name';
            $additiveSortOrdering = 'asc';
        }
        if ($sortedBy == 'is_quiz_is_homepage') {
            $sortByField          = 'is_quiz';
            $sortOrdering         = 'desc';
            $additiveSortByField  = 'is_homepage';
            $additiveSortOrdering = 'asc';
        }
        if ($sortedBy == 'last_created') {
            $sortByField          = 'created_at';
            $sortOrdering         = 'desc';
            $additiveSortByField  = 'id';
            $additiveSortOrdering = 'desc';
        }

        $totalVotesCount = Vote::getByName($filterName, true)
            ->getByStatus($filterStatus)
            ->getByIsQuiz($filterIsQuiz)
            ->getByIsHomepage($filterIsHomepage)
            ->getByVoteCategoryId($filterVoteCategoryId)
            ->count();

        /* Get data (by request parameters) and format output data  */
        $votes = Vote::getByName($filterName, true)
            ->getByStatus($filterStatus)
            ->getByIsQuiz($filterIsQuiz)
            ->getByIsHomepage($filterIsHomepage)
            ->with('voteCategory')
            ->with('creator')
            ->with('articles')
            ->getByVoteCategoryId($filterVoteCategoryId)
            ->orderBy($sortByField, $sortOrdering)
            ->orderBy($additiveSortByField, $additiveSortOrdering)
            ->paginate($paginationPerPage, array('*'), 'page', $page)
            ->through(function ($vote) {
                $vote->status_label         = Vote::getStatusLabel($vote->status);
                $vote->is_quiz_label        = Vote::getIsQuizLabel($vote->is_quiz);
                $vote->is_homepage_label    = Vote::getIsHomepageLabel($vote->is_homepage);
                $vote->created_at_formatted = DateConv::getFormattedDateTime($vote->created_at);

                return $vote;
            });

        return [
            'votes'             => VoteResource::collection($votes),
            'totalVotesCount'   => $totalVotesCount,
            'paginationPerPage' => $paginationPerPage,
        ];
    }

    /**
     * Get an individual Vote model by id
     *
     * @param int $id
     *
     * @return Model
     */
    public function get(int $id): JsonResponse|MessageBag
    {
        /*
        * Checks if current logged user has access to this method by user's state
        */
        $this->userAccountManager->checkPermissions(
            [
                UserAccountManagerEnum::UAM_USER_HAS_ACTIVE_STATUS,
                UserAccountManagerEnum::UAM_USER_IS_DEVELOPERS_TEAM,
                UserAccountManagerEnum::UAM_USER_HAS_NO_BAN,
                UserAccountManagerEnum::UAM_USER_HAS_VOTES_ACCESS,
            ],
            "get"
        );
        $loggedUser = Auth::user();
        $vote       = Vote::getById($id)
            ->with('creator')
            ->with('voteCategory')
            ->firstOrFail();   // LAST_COMMENTED

        /*
        * Additive Validation rule : vote not found - returns HTTP_RESPONSE_NOT_FOUND (404) onerror
        */
        $rulesVoteUpdate = new RulesVoteUpdate(actionLabel: "get", id: $id, vote: $vote, loggedUser: $loggedUser);
        $retResults      = $rulesVoteUpdate->check(
            rulesToValidate: [
                UpdateVoteRules::UVR_VOTE_NOT_FOUND_BY_ID,
            ]
        );
        if ( ! $retResults['result']) {
            return response()->json(
                ['message' => $retResults['message'], 'returnCode' => $retResults['returnCode']],
                HTTP_RESPONSE_BAD_REQUEST
            );
        }

        return response()->json(['vote' => (new VoteResource($vote))], HTTP_RESPONSE_OK); // 200
    }

    /**
     * Store new validated Vote model in storage
     *
     * @return \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag
     *
     * @return Model
     */
    public function store(array $data, bool $makeValidation = false): JsonResponse|MessageBag
    {
        /*
        * Checks if current logged user has access to this method by user's state
        */
        $this->userAccountManager->checkPermissions(
            [
                UserAccountManagerEnum::UAM_USER_HAS_ACTIVE_STATUS,
                UserAccountManagerEnum::UAM_USER_IS_DEVELOPERS_TEAM,
                UserAccountManagerEnum::UAM_USER_HAS_POSITIVE_BALANCE,
                UserAccountManagerEnum::UAM_USER_HAS_NO_BAN,
                UserAccountManagerEnum::UAM_USER_HAS_VOTES_ACCESS,
            ],
            "store"
        );

        $loggedUser = Auth::user();

        $rulesVoteUpdate = new RulesVoteUpdate(actionLabel: "store", id: null, vote: null, loggedUser: $loggedUser);
        $retResults      = $rulesVoteUpdate->check(rulesToValidate: [
            UpdateVoteRules::UVR_ONLY_CREATOR_OR_ADMIN_CAN_UPDATE_VOTE,
        ]);
        if ( ! $retResults['result']) {
            return response()->json(
                ['message' => $retResults['message'], 'returnCode' => $retResults['returnCode']],
                HTTP_RESPONSE_BAD_REQUEST
            );
        }

        if ($makeValidation) {
            /* Validate Vote by rules in model's methods  */
            $voteValidationRulesArray = Vote::getValidationRulesArray(voteId: null, skipFieldsArray: []);
            $validator                = \Illuminate\Support\Facades\Validator::make(
                $data,
                $voteValidationRulesArray,
                Vote::getValidationMessagesArray()
            );
            if ($validator->fails()) {
                $errorMsg = $validator->getMessageBag();

                return response()->json(['errors' => $errorMsg], HTTP_RESPONSE_BAD_REQUEST); // 400
            }
        } // if ($makeValidation) {

        DB::beginTransaction();
        try {
            $vote = Vote::create([
                'name'             => $data['name'],
                'description'      => $data['description'],
                'creator_id'       => $data['creator_id'],
                'vote_category_id' => $data['vote_category_id'],
                'is_quiz'          => $data['is_quiz'],
                'is_homepage'      => $data['is_homepage'],
                'status'           => $data['status'],
                'ordering'         => $data['ordering'] ?? Vote::max('ordering') + 1,
            ]);
            DB::Commit();

            $vote->load('voteCategory', 'creator');

            return response()->json(['vote' => (new VoteResource($vote))], HTTP_RESPONSE_OK_RESOURCE_CREATED); // 201
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
     * Update validated Vote model with given array in storage
     *
     * @param string $voteId
     *
     * @param array $data
     *
     * @return bool - if update was succesfull
     */
    public function update(int $id, array $data, bool $makeValidation = false): JsonResponse|MessageBag
    {

        /*
* Checks if current logged user has access to this method by user's state
*/
        $this->userAccountManager->checkPermissions(
            [
                UserAccountManagerEnum::UAM_USER_HAS_ACTIVE_STATUS,
                UserAccountManagerEnum::UAM_USER_IS_DEVELOPERS_TEAM,
                UserAccountManagerEnum::UAM_USER_HAS_POSITIVE_BALANCE,
                UserAccountManagerEnum::UAM_USER_HAS_NO_BAN,
                UserAccountManagerEnum::UAM_USER_HAS_VOTES_ACCESS,
            ],
            "update"
        );

        $loggedUser      = Auth::user();
        $vote            = Vote::find($id);
        $rulesVoteUpdate = new RulesVoteUpdate(actionLabel: "update", id: $id, vote: $vote, loggedUser: $loggedUser);
        $retResults      = $rulesVoteUpdate->check(rulesToValidate: [
            UpdateVoteRules::UVR_VOTE_NOT_FOUND_BY_ID,
            UpdateVoteRules::UVR_UPDATED_VOTE_MUST_BE_NOT_ACTIVE,
            UpdateVoteRules::UVR_ONLY_CREATOR_OR_ADMIN_CAN_UPDATE_VOTE,
        ]);
        if ( ! $retResults['result']) {
            return response()->json(
                ['message' => $retResults['message'], 'returnCode' => $retResults['returnCode']],
                HTTP_RESPONSE_BAD_REQUEST
            );
        }

        if ($makeValidation) {
            /* Validate Vote by rules in model's methods  */
            $voteValidationRulesArray = Vote::getValidationRulesArray(
                voteId: $id,
                skipFieldsArray: ['creator_id', 'status']
            );
            $validator                = \Illuminate\Support\Facades\Validator::make(
                $data,
                $voteValidationRulesArray,
                Vote::getValidationMessagesArray()
            );
            if ($validator->fails()) {
                $errorMsg = $validator->getMessageBag();

                return response()->json(['errors' => $errorMsg], HTTP_RESPONSE_BAD_REQUEST); // 400
            }
        } // if ($makeValidation) {

        $data['updated_at'] = Carbon::now(config('app.timezone'));
        DB::beginTransaction();
        try {
            $vote->update($data);
            DB::Commit();
            $vote->load('voteCategory', 'creator');

            return response()->json(['vote' => (new VoteResource($vote))], HTTP_RESPONSE_OK_RESOURCE_UPDATED); // 205
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
     * Remove the specified Vote model from storage
     *
     * @param Vote model $model
     *
     * @return void
     */
    public function delete(int $id): Response|JsonResponse|MessageBag
    {
        /*
        * Checks if current logged user has access to this method by user's state
        */
        $this->userAccountManager->checkPermissions(
            [
                UserAccountManagerEnum::UAM_USER_HAS_ACTIVE_STATUS,
                UserAccountManagerEnum::UAM_USER_IS_DEVELOPERS_TEAM,
                UserAccountManagerEnum::UAM_USER_HAS_POSITIVE_BALANCE,
                UserAccountManagerEnum::UAM_USER_HAS_NO_BAN,
                UserAccountManagerEnum::UAM_USER_HAS_VOTES_ACCESS,
            ],
            "delete"
        );

        $loggedUser      = Auth::user();
        $vote            = Vote::find($id);
        $rulesVoteUpdate = new RulesVoteUpdate(actionLabel: "delete", id: $id, vote: $vote, loggedUser: $loggedUser);
        $retResults      = $rulesVoteUpdate->check(rulesToValidate: [
            UpdateVoteRules::UVR_VOTE_NOT_FOUND_BY_ID,
            UpdateVoteRules::UVR_DELETED_VOTE_MUST_BE_INACTIVE,
            UpdateVoteRules::UVR_DELETED_VOTE_MUST_BE_EMPTY,
        ]);
        if ( ! $retResults['result']) {
            return response()->json(
                ['message' => $retResults['message'], 'returnCode' => $retResults['returnCode']],
                HTTP_RESPONSE_BAD_REQUEST
            );
        }

        DB::beginTransaction();
        try {
            $vote->delete();
            DB::commit();

            return response()->noContent();
        } catch (\Exception $e) {
            DB::rollback();

            return \App\Library\AppCustomException::getInstance()::raiseChannelError(
                errorMsg: $e->getMessage(),
                exceptionClass: \Exception::class,
                file: __FILE__,
                line: __LINE__
            );
        } catch (\Error $e) {
            DB::rollback();

            return \App\Library\AppCustomException::getInstance()::raiseChannelError(
                errorMsg: $e->getMessage(),
                exceptionClass: \Error::class,
                file: __FILE__,
                line: __LINE__
            );
        }
    }

    /*  Vote CRUD(implements CrudInterface) BLOCK END */


    /*  Vote ACTIVATE/DEACTIVATE(implements ItemStatusModificationInterface) BLOCK START */

    /**
     * Activate existing Vote model in db.
     *
     * @param id - $id of action vote
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(int $id): \Illuminate\Http\JsonResponse|\Illuminate\Support\MessageBag
    {
        /*
        * Checks if current logged user has access to this method by user's state
        */
        $this->userAccountManager->checkPermissions(
            [
                UserAccountManagerEnum::UAM_USER_HAS_ACTIVE_STATUS,
                UserAccountManagerEnum::UAM_USER_IS_DEVELOPERS_TEAM,
                UserAccountManagerEnum::UAM_USER_HAS_NO_BAN,
                UserAccountManagerEnum::UAM_USER_HAS_VOTES_ACCESS,
            ],
            "activate"
        );
        $loggedUser = Auth::user();
        $vote       = Vote::find($id);

        $rulesVoteUpdate = new RulesVoteUpdate(actionLabel: "activate", id: $id, vote: $vote, loggedUser: $loggedUser);
        $retResults      = $rulesVoteUpdate->check(rulesToValidate: [
            UpdateVoteRules::UVR_VOTE_NOT_FOUND_BY_ID,
            UpdateVoteRules::UVR_VOTE_IS_ALREADY_ACTIVE,
        ]);
        if ( ! $retResults['result']) {
            return response()->json(
                ['message' => $retResults['message'], 'returnCode' => $retResults['returnCode']],
                HTTP_RESPONSE_BAD_REQUEST
            );
        }

        DB::beginTransaction();
        try {
            $vote->status     = 'A'; // A=>Active,
            $vote->updated_at = Carbon::now(config('app.timezone'));

            if ($vote->save()) {
                DB::commit();

                return response()->json(
                    ['vote' => (new VoteResource($vote))],
                    HTTP_RESPONSE_OK_RESOURCE_UPDATED
                );
            }
        } catch (\Exception $e) {
            DB::rollback();

            return \App\Library\AppCustomException::getInstance()::raiseChannelError(
                errorMsg: $e->getMessage(),
                exceptionClass: \Exception::class,
                file: __FILE__,
                line: __LINE__
            );
        } catch (\Error $e) {
            DB::rollback();

            return \App\Library\AppCustomException::getInstance()::raiseChannelError(
                errorMsg: $e->getMessage(),
                exceptionClass: \Error::class,
                file: __FILE__,
                line: __LINE__
            );
        }

    }

    /**
     * Deactivate existing Vote model in db.
     *
     * @param id - $id of action vote
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deactivate(int $id): \Illuminate\Http\JsonResponse|\Illuminate\Support\MessageBag
    {
        /*
        * Checks if current logged user has access to this method by user's state
        */
        $this->userAccountManager->checkPermissions(
            [
                UserAccountManagerEnum::UAM_USER_HAS_ACTIVE_STATUS,
                UserAccountManagerEnum::UAM_USER_IS_DEVELOPERS_TEAM,
                UserAccountManagerEnum::UAM_USER_HAS_NO_BAN,
                UserAccountManagerEnum::UAM_USER_HAS_VOTES_ACCESS,
            ],
            "deactivate"
        );
        $loggedUser      = Auth::user();
        $vote            = Vote::find($id);
        $rulesVoteUpdate = new RulesVoteUpdate(actionLabel: "deactivate", id: $id, vote: $vote, loggedUser:
            $loggedUser);
        $retResults      = $rulesVoteUpdate->check(rulesToValidate: [
            UpdateVoteRules::UVR_VOTE_NOT_FOUND_BY_ID,  //
            UpdateVoteRules::UVR_VOTE_IS_ALREADY_INACTIVE,
        ]);
        if ( ! $retResults['result']) {
            return response()->json(
                ['message' => $retResults['message'], 'returnCode' => $retResults['returnCode']],
                HTTP_RESPONSE_BAD_REQUEST
            );
        }

        DB::beginTransaction();
        try {
            $vote->status     = 'I'; // I=>Inactive
            $vote->updated_at = Carbon::now(config('app.timezone'));

            if ($vote->save()) {
                DB::commit();

                return response()->json(
                    ['vote' => (new VoteResource($vote))],
                    HTTP_RESPONSE_OK_RESOURCE_UPDATED
                );
            }
        } catch (\Exception $e) {
            DB::rollback();

            return \App\Library\AppCustomException::getInstance()::raiseChannelError(
                errorMsg: $e->getMessage(),
                exceptionClass: \Exception::class,
                file: __FILE__,
                line: __LINE__
            );
        } catch (\Error $e) {
            DB::rollback();

            return \App\Library\AppCustomException::getInstance()::raiseChannelError(
                errorMsg: $e->getMessage(),
                exceptionClass: \Error::class,
                file: __FILE__,
                line: __LINE__
            );
        }

    }
    /*  Vote ACTIVATE/DEACTIVATE(implements ItemStatusModificationInterface) BLOCK END */


    /*  Vote CRUD(implements UploadedImageInterface) BLOCK Start */

    /**
     * Upload image in storage under relative path storage/app/public/models/model-ID/image_name.ext
     *
     * @param Request $request - request with uploaded file
     *
     * @param string $imageFieldName - image key in request with uploaded file
     *
     * @return array :    result === 1 if upload was successfull, uploadedImagePath - relative path of uploaded file
     * under storage, imageName - name  of uploaded file
     */
    public function imageUpload(int $id, Request $request, string $imageFieldName): JsonResponse|MessageBag
    {
        /*
        * Checks if current logged user has access to this method by user's state
        */
        $this->userAccountManager->checkPermissions(
            [
                UserAccountManagerEnum::UAM_USER_HAS_ACTIVE_STATUS,
                UserAccountManagerEnum::UAM_USER_IS_DEVELOPERS_TEAM,
                UserAccountManagerEnum::UAM_USER_HAS_POSITIVE_BALANCE,
                UserAccountManagerEnum::UAM_USER_HAS_NO_BAN,
                UserAccountManagerEnum::UAM_USER_HAS_VOTES_ACCESS,
            ],
            "imageUpload"
        );
        $loggedUser      = Auth::user();
        $vote            = Vote::find($id);
        $rulesVoteUpdate = new RulesVoteUpdate(actionLabel: "delete", id: $id, vote: $vote, loggedUser: $loggedUser);
        $retResults      = $rulesVoteUpdate->check(rulesToValidate: [
            UpdateVoteRules::UVR_VOTE_NOT_FOUND_BY_ID,
            UpdateVoteRules::UVR_CAN_NOT_UPLOAD_IMAGE_IN_ACTIVE_VOTE,
        ]);
        if ( ! $retResults['result']) {
            return response()->json(
                ['message' => $retResults['message'], 'returnCode' => $retResults['returnCode']],
                HTTP_RESPONSE_BAD_REQUEST
            );
        }

        $uploadedFileManagement = app(UploadedFileManagementInterface::class);
        $uploadedFileManagement->setRequest($request);
        $uploadedFileManagement->setImageFieldName($imageFieldName);
        $validated = $uploadedFileManagement->validate(UploadImageRules::UIR_VOTE_IMAGE);

        if ( ! $validated['result']) {
            throw new UploadedImageHasInvalidProps($validated['message']);
        }
        if ($validated['result']) {
            DB::beginTransaction();
            try {
                $uploadedRet = $uploadedFileManagement->upload(model: $vote, fileType:
                    'image');
                if ($uploadedRet['result']) {
                    $vote->image      = $uploadedRet['imageName'];
                    $vote->updated_at = Carbon::now(config('app.timezone'));
                    $vote->save();
                }
                DB::Commit();
                $vote->load('media');
                $vote->load('voteImage');

                return response()->json([
                    'result' => true,
                    'vote'   => (new VoteResource($vote))
                ], HTTP_RESPONSE_OK_RESOURCE_CREATED); // 201
            } catch (\Exception $e) {
                DB::rollback();
            }
        }

        return response()->json([
            'result' => false,
            'vote'   => null,
        ], HTTP_RESPONSE_BAD_REQUEST);
    }

    /**
     * Remove image from storage under relative path storage/app/public/models/model-ID/image_name.ext by Vote Id
     *
     * @param int $id - Vote Id
     *
     * @return void
     */
    public function imageClear(int $id): bool|\Illuminate\Http\JsonResponse
    {
        /*
        * Checks if current logged user has access to this method by user's state
        */
        $this->userAccountManager->checkPermissions(
            [
                UserAccountManagerEnum::UAM_USER_HAS_ACTIVE_STATUS,
                UserAccountManagerEnum::UAM_USER_IS_DEVELOPERS_TEAM,
                UserAccountManagerEnum::UAM_USER_HAS_NO_BAN,
                UserAccountManagerEnum::UAM_USER_HAS_VOTES_ACCESS,
            ],
            "imageClear"
        );
        $loggedUser      = Auth::user();
        $vote            = Vote::find($id);
        $rulesVoteUpdate = new RulesVoteUpdate(actionLabel: "delete", id: $id, vote: $vote, loggedUser: $loggedUser);
        $retResults      = $rulesVoteUpdate->check(rulesToValidate: [
            UpdateVoteRules::UVR_VOTE_NOT_FOUND_BY_ID,
            UpdateVoteRules::UVR_CAN_NOT_UPLOAD_IMAGE_IN_ACTIVE_VOTE,
        ]);
        if ( ! $retResults['result']) {
            return response()->json(
                ['message' => $retResults['message'], 'returnCode' => $retResults['returnCode']],
                $retResults['returnCode']
            );
        }

        $uploadedFileManagement = app(UploadedFileManagementInterface::class);

        DB::beginTransaction();
        try {
            $success = $uploadedFileManagement->remove($vote);
            DB::Commit();

            return response()->json([
                'result' => true,
                'vote'   => (new VoteResource($vote))
            ], HTTP_RESPONSE_OK_RESOURCE_CREATED); // 201
        } catch (\Exception $e) {
            DB::rollback();
        }

        $vote->updated_at = Carbon::now(config('app.timezone'));
        $vote->save();

        return $success;
    }

    /*  Vote CRUD(implements UploadedImageInterface) BLOCK END */

}
