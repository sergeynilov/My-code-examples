<?php

namespace App\Library;

use Carbon\Carbon;
use App\Models\{Article, Vote, VoteItem, MyTag};
use App\Models\Taggable as MyTaggable;
use App\Http\Resources\VoteResource;
use DB;
use App\Exceptions\VoteBuilderCustomException;
use App\Library\Services\Interfaces\LogInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use App\Library\Services\ModelImageUpload;
use App\Events\VoteCreated;

/*
Class for creating of vote by provided data(on successful validation)
*

Example of use :

a) Adding new vote :

        $articleContentToStore = [
            'title'        => 'Article Content title #2 Somenew Text',
            'text'         => 'Article Content title lorem <i>ipsum dolor sit</i> amet, consectetur adipiscing elit, sed do eiusmod  tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim  veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea  commodo consequat. Duis aute irure dolor in reprehenderit in voluptate  velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint  occaecat cupidatat non proident, sunt in culpa qui officia deserunt  mollit anim id est laborum',
            'text_shortly' => 'Article Content title text shortly',
            'creator_id'   => $this->creatorId,
            'published'    => true
        ];

        $voteBuilder = (new VoteBuilder())
            ->setName('New vote ' . Carbon::now(config('app.timezone')))
            ->setDescription('Long description text')
            ->setMetaDescription('Long meta_description text')
            ->setMetaKeywords(['Long meta_meta_keywords text 1', 'Long meta_meta_keywords text 2'])
            ->setCreatorId($this->creatorId, isCreatorMemberTeamUser: true)
            ->setVoteCategoryId(2)
            ->setIsQuiz(true)
            ->setIsHomepage(true)
            ->setStatus(Vote::STATUS_ACTIVE)
            ->setOrdering(9)
            ->setVoteItem(name: 'New Vote Item 1', ordering: 254, isCorrect: true, image: null, imageUrl: 'image-holder')
            ->setVoteItem(name: 'New Vote Item 2', ordering: null, isCorrect: false, image: null, imageUrl:
                'image-holder2')

            ->setArticleById(
                1,
                active: false,
                dateExpiredAt: Carbon::now()->setMonth(),
                supervisorId: 2,
                supervisorNotes: 'Notes started ...'
            )
            ->setArticleByContent(articleContent: $articleContentToStore, active: false, dateExpiredAt:
                Carbon::now()->setMonth(), supervisorId: 2, supervisorNotes: 'Notes started ...')

            ->setTagByName(value: 'New Tag 2')
            ->setTagByName(value: 'New Tag 987')
            ->setTagById(value: 2+99)

            ->validate(customValidation: true, isInsert: true)
            ->setUploadedImage(value: 'http://local-ms-votes.com/Init/Img/0d441.png')
            ->store();

        $this->logger->writeInfo(' JobVotesCreationTest AFTER VALIDATION  $voteBuilder->getErrorCode() ' . $voteBuilder->getErrorCode());
        $this->logger->writeInfo(' JobVotesCreationTest AFTER VALIDATION  $voteBuilder->getValidationMessages() ' .
                                 print_r($voteBuilder->getValidationMessages(), true));

        // Check validation/error code and error message
        if ($voteBuilder->getErrorCode() and $voteBuilder->getErrorCode() === HTTP_RESPONSE_UNPROCESSABLE_ENTITY) { // there is a validation error
            echo '$voteBuilder->errorCode()::' . print_r($voteBuilder->getErrorCode(), true) . chr(13);
            echo '$voteBuilder->getValidationMessages()::' . print_r(
                $voteBuilder->getValidationMessages(),
                true
            ) . chr(13);
        }

        if ($voteBuilder->getErrorCode() and $voteBuilder->getErrorCode() !== HTTP_RESPONSE_UNPROCESSABLE_ENTITY) { // there is a runtime error
            echo '$voteBuilder->errorCode()::' . print_r($voteBuilder->getErrorCode(), true) . chr(13);
            echo '$voteBuilder->errorMessage()::' . print_r($voteBuilder->getErrorMessage(), true) . chr(13);
        }

        // Get created vote model
        $vote = $voteBuilder->getVote(asResource: true);

        if ($voteBuilder->getErrorCode() === 0) {
            $createdVoteId = '';
            if ($vote instanceof \Illuminate\Http\JsonResponse) {
                $voteContent = $vote->getContent();
                $voteContent   = json_decode($voteContent);
                $createdVoteId = $voteContent->vote->id;
            } else {
                $createdVoteId = $vote->id;
            }
            $this->logger->writeInfo(' JobVotesCreationTest END WITH SUCCESS $createdVoteId : ' . $createdVoteId);
        }
        else {
            $this->logger->writeInfo(' JobVotesCreationTest END with errors ');
        }

 */

class VoteBuilder
{
    // Fields of Vote model
    protected string $name;
    protected string $description;
    protected string $meta_description;
    protected string $meta_keywords;
    protected int $creator_id;
    protected bool $isCreatorMemberTeamUser;
    protected int $vote_category_id;
    protected bool $is_quiz;
    protected bool $is_homepage;
    protected string $status;
    protected int|null $ordering;

    // Fields of Vote model joined into an array
    protected array $voteData = [];

    // Array of fields to be added for VoteItem model
    protected array $addVoteItemsArray = [];

    // Array of ids to be removed from VoteItem model
    protected array $removeVoteItemsArray = [];

    // Array of ids(Referenced Article model) to be added for VoteArticle model
    protected array $assignArticlesByIdArray = [];

    // Array of ids(Referenced Article model) to be removed from VoteArticle model
    protected array $revokeArticlesByIdArray = [];

    // Array of subarrays (all Article model fields) to be added for VoteArticle model
    protected array $addArticlesByContentArray = [];

    // Array of ids(Referenced Tag model) to be removed from VoteTag model
    protected array $assignTagsByIdArray = [];

    // Array of ids(Referenced Tag model) to be removed from VoteTag model
    protected array $revokeTagsByIdArray = [];

    // Absolute url of image to be uploaded and attached for the created/updated vote with Media Library
    protected ?string $uploadedImage;

    // Array of subarrays (all Tag model fields) to be added for VoteTag model
    protected array $tagsByNameArray = [];

    // All validation errors made during validating/before storing/updating
    protected array $validationMessages = [];

    // Message of run time error
    protected string $errorMessage;
    // Code of run time error
    protected int $errorCode;

    // ID of new(store  method) or existing(update method) vote
    protected int $voteId;

    // Vote model(store  method) or existing(update method)
    protected Vote $vote;

    // Interface for logging messages
    protected $logger;

    public function __construct()
    {
        $this->logger = app(LogInterface::class);
    }

    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    public function setDescription(string $value): self
    {
        $this->description = $value;

        return $this;
    }

    public function setMetaDescription(string $value): self
    {
        $this->meta_description = $value;

        return $this;
    }

    public function setMetaKeywords(array $value): self
    {
        $this->meta_keywords = json_encode($value);

        return $this;
    }

    public function setCreatorId(int $value, bool $isCreatorMemberTeamUser = false): self
    {
        $this->creator_id              = $value;
        $this->isCreatorMemberTeamUser = $isCreatorMemberTeamUser;

        return $this;
    }


    public function setVoteCategoryId(int $value): self
    {
        $this->vote_category_id = $value;

        return $this;
    }


    public function setIsQuiz(bool $value): self
    {
        $this->is_quiz = $value;

        return $this;
    }

    public function setIsHomepage(bool $value): self
    {
        $this->is_homepage = $value;

        return $this;
    }


    public function setStatus(string $value): self
    {
        $this->status = $value;
        // only Gold User can publish active vote
        if ( ! $this->isCreatorMemberTeamUser and $value === Vote::STATUS_ACTIVE) {
            $this->status = Vote::STATUS_NEW;
        }

        return $this;
    }

    public function setOrdering(int $value = null): self
    {
        $this->ordering = $value;

        return $this;
    }

    /* VoteItem*/
    public function setVoteItem(
        string $name,
        int $ordering = null,
        bool $isCorrect,
        string $image = null,
        string $imageUrl = null
    ): self {
        $this->addVoteItemsArray[] = [
            'name'       => $name,
            'ordering'   => $ordering,
            'is_correct' => $isCorrect,
            'image'      => $image,
            'imageUrl'   => $imageUrl,
        ];

        return $this;
    }

    public function removeVoteItemById(int $voteItemById): self
    {
        $this->removeVoteItemsArray[] = $voteItemById;

        return $this;
    }

    public function setArticleById(
        int $articleId,
        bool $active = false,
        $dateExpiredAt = null,
        int $supervisorId = null,
        string $supervisorNotes = ''
    ): self {
        $this->assignArticlesByIdArray[] = [
            'article_id'       => $articleId,
            'active'           => $active,
            'expired_at'       => $dateExpiredAt,
            'supervisor_id'    => $supervisorId,
            'supervisor_notes' => $supervisorNotes,
        ];

        return $this;
    }

    public function revokeArticleById(int $articleById): self
    {
        $this->revokeArticlesByIdArray[] = $articleById;

        return $this;
    }

    public function setArticleByContent(
        array $articleContent,
        bool $active,
        Carbon $dateExpiredAt,
        int $supervisorId,
        string $supervisorNotes
    ): self {

        if ( ! $this->isCreatorMemberTeamUser) {
            throw new VoteBuilderCustomException('Only "gold" user can add new article !');
        }
        $this->addArticlesByContentArray[] = [
            'articleContent'  => $articleContent,
            'active'          => $active,
            'dateExpiredAt'   => $dateExpiredAt,
            'supervisorId'    => $supervisorId,
            'supervisorNotes' => $supervisorNotes,
        ];

        return $this;
    }

    public function setTagById(int $value = null): self
    {
        $this->assignTagsByIdArray[] = [
            'tag_id' => $value
        ];

        return $this;
    }

    public function revokeTagById(int $tagById): self
    {
        $this->revokeTagsByIdArray[] = $tagById;

        return $this;
    }

    public function setTagByName(string $value = null): self
    {
        if ( ! $this->isCreatorMemberTeamUser) {
            throw new VoteBuilderCustomException('Only "gold" user can add new tag !');
        }
        $this->tagsByNameArray[] = [
            'tag_name' => $value,
        ];

        return $this;
    }

    public function setUploadedImage(string $value = null): self
    {
        $this->uploadedImage = $value;

        return $this;
    }

    /*
    Validate all data before store/update method
    */
    public function validate(bool $customValidation = false, bool $isInsert = false): self
    {
        $this->validationMessages = [];
        $this->errorCode          = 0;

        $this->voteData = [
            'name'             => $this->name,
            'description'      => $this->description,
            'creator_id'       => $this->creator_id,
            'vote_category_id' => $this->vote_category_id,
            'is_quiz'          => $this->is_quiz,
            'is_homepage'      => $this->is_homepage,
            'status'           => $this->status,
            'ordering'         => $this->ordering ?? Vote::max('ordering') + 1,
        ];

        $validated = $this->validateVote();
        if ( ! empty($validated)) {
            $this->logger->writeError(
                s: $validated,
                info: 'validateVote method : ',
                file: __FILE__,
                line: __LINE__,
                isDie: false
            );
        }

        $validated = $this->validateVoteItems();
        if ( ! empty($validated)) {
            $this->logger->writeError(s: $validated, info: 'validateVoteItems method : ', file: __FILE__, line:
                __LINE__, isDie: false);
        }

        $validated = $this->validateArticlesByIds();
        if ( ! empty($validated)) {
            $this->logger->writeError(s: $validated, info: 'validateArticlesByIds method : ', file: __FILE__, line:
                __LINE__, isDie: false);
        }

        // Need to make some custom validation/App logics
        if ($customValidation) {
            $isCorrectNumbersCount = 0;
            foreach ($this->addVoteItemsArray as $voteItemData) {
                if ($voteItemData['is_correct']) {
                    $isCorrectNumbersCount++;
                }
            }
            $voteItemsWithIsCorrectCount = 0;
            if ( ! $isInsert) {
                // for updated data need to check number of existing VoteItems with IsCorrect = true
                $voteItemsWithIsCorrectCount = VoteItem::getByVoteId()
                    ->getByNotInIds($this->removeVoteItemsArray)
                    ->getByIsCorrect(true)
                    ->count();
            }

            // Calculate existing VoteItems + added in $this->addVoteItemsArray and which are not deleted in
            // $this->removeVoteItemsArray with IsCorrect = true
            if (($isCorrectNumbersCount + $voteItemsWithIsCorrectCount) !== 1) {
                throw new VoteBuilderCustomException('Must be only 1 vote item marked as is_correct !');
            }
        }

        return $this;
    }
    //

    /*
     * Validate Vote
     *
     * If no validation errors then empty array is returned
     *
     * If there are validation errors then array with errors is returned
     *
     * */
    protected function validateVote(): array
    {
        $voteValidationRulesArray = Vote::getValidationRulesArray(
            voteId: null,
            skipFieldsArray: []
        );
        $validator                = \Illuminate\Support\Facades\Validator::make(
            $this->voteData,
            $voteValidationRulesArray,
            Vote::getValidationMessagesArray()
        );
        if ($validator->fails()) {
            $this->validationMessages = $validator->messages()->all();
            $this->errorCode          = HTTP_RESPONSE_UNPROCESSABLE_ENTITY;

            return $this->validationMessages;
        }

        return [];
    }

    /*
     * Validate Vote items
     *
     * If no validation errors then empty array is returned
     *
     * If there are validation errors then array with errors is returned
     *
     * */
    protected function validateVoteItems(): array
    {
        foreach ($this->addVoteItemsArray as $voteItemData) {
            $voteItemValidationRulesArray = VoteItem::getValidationRulesArray(
                voteId: null,
                voteItemId: null,
                skipFieldsArray: ['vote_id']
            );
            $validator                    = \Illuminate\Support\Facades\Validator::make(
                $voteItemData,
                $voteItemValidationRulesArray,
                VoteItem::getValidationMessagesArray()
            );
            if ($validator->fails()) {
                $this->validationMessages = $validator->messages()->all();
                $this->errorCode          = HTTP_RESPONSE_UNPROCESSABLE_ENTITY;

                return $this->validationMessages;
            }
        }

        return [];
    }

    /*
     * Validate articles By Ids(check valid articles reference ids)
     *
     * If no validation errors then empty array is returned
     *
     * If there are validation errors then array with errors is returned
     *
     * */
    protected function validateArticlesByIds(): array
    {
        foreach ($this->assignArticlesByIdArray as $articleData) {
            $articleValidationRulesArray = ['article_id' => 'required|integer|exists:articles,id'];
            $validator                   = \Illuminate\Support\Facades\Validator::make(
                Arr::only($articleData, ['article_id']),
                $articleValidationRulesArray,
                Article::getValidationMessagesArray()
            );
            if ($validator->fails()) {
                $this->validationMessages = $validator->messages()->all();
                $this->errorCode          = HTTP_RESPONSE_UNPROCESSABLE_ENTITY;

                return $this->validationMessages;
            }

        }

        return [];
    }


    /*
     * Add new vote/Update existing vote
     *
     * with all related data
     *
     * @return void
     */
    protected function saveData(): void
    {
        if (empty($this->voteId)) {  // Add new vote
            $this->vote = Vote::create($this->voteData);
        } else {  // Update existing vote
            $this->vote = Vote::findOrFail($this->voteId);
            $this->vote->update($this->voteData);

            // remove all existing vote_items
            foreach ($this->removeVoteItemsArray as $revokeVoteItemById) {
                $voteItem = VoteItem::find($revokeVoteItemById);
                if ($voteItem !== null) {
                    $voteItem->delete();
                }
            }

            // remove all existing articles
            foreach ($this->revokeArticlesByIdArray as $revokeArticleById) {
                $this->vote->articles()->detach($revokeArticleById);
            }

            // remove all existing tags
            foreach ($this->revokeTagsByIdArray as $revokeTagById) {
                $taggable = MyTaggable::getByTaggableType(\App\Models\Vote::class)
                    ->getByTaggableId($this->voteId)
                    ->getByTagId($revokeTagById)
                    ->first();
                if ($taggable != null) {
                    $taggable->delete();
                }

//                $this->vote->articles()->detach($revokeTagById);
            }
        }

        // Add vote items from array of data
        foreach ($this->addVoteItemsArray as $voteItemData) {
            $voteItem = VoteItem::create([
                'name'       => $voteItemData['name'],
                'vote_id'    => $this->vote->id,
                'is_correct' => $voteItemData['is_correct'] ?? '0',
                'ordering'   => $voteItemData['ordering'] ?? VoteItem::max('ordering') + 1,
            ]);
        }

        // Assign (existing)articles by id from array to created vote
        foreach ($this->assignArticlesByIdArray as $article) {
            $pivotData = [
                'active'           => $article['active'],
                'expired_at'       => $article['expired_at'],
                'supervisor_id'    => $article['supervisor_id'],
                'supervisor_notes' => $article['supervisor_notes']
            ];
            $this->vote->articles()->attach($article['article_id'], $pivotData);
        }


        // Add (new)Articles from array of data and assign them to created vote
        foreach ($this->addArticlesByContentArray as $articleByContent) {
            $article = Article::create($articleByContent['articleContent']);
            $this->vote->articles()->attach($article->id, [
                'active'           => $articleByContent['active'],
                'expired_at'       => $articleByContent['dateExpiredAt'],
                'supervisor_id'    => $articleByContent['supervisorId'],
                'supervisor_notes' => $articleByContent['supervisorNotes'],
            ]);
        }

        $votesTagType = with(new MyTag)->getVotesTagType();

        // Assign (existing)tags by id from array to created vote
        foreach ($this->assignTagsByIdArray as $tagById) {
            $tagName = MyTag::find($tagById);
            if ( ! empty($tagName)) {
                $this->vote->syncTagsWithType([$tagName], $votesTagType);
            }
        }

        // Add (new)Tags from array of data and assign them to created vote
        foreach ($this->tagsByNameArray as $tagName) {
            if ( ! empty($tagName['tag_name'])) {
                $this->vote->syncTagsWithType([$tagName['tag_name']], $votesTagType);
            }
        }

        /// Upload image with absolute url
        $this->uploadAttachImage($this->uploadedImage);
    }
    // protected function saveData()


    /*
    *
    Store new vote
    *
    */
    public function store(): self
    {
        // There are validation error messages - skip adding data
        if ($this->errorCode === HTTP_RESPONSE_UNPROCESSABLE_ENTITY and count($this->validationMessages)) {
            return $this;
        }

        $this->errorMessage = '';
        $this->errorCode    = 0;

        DB::beginTransaction();
        try {
            $this->saveData();
        } catch (\Exception $e) {
            DB::rollback();
            $this->errorMessage = $e->getMessage();
            $this->errorCode    = HTTP_RESPONSE_INTERNAL_SERVER_ERROR;

            return $this;
        }
        DB::Commit();

        VoteCreated::dispatch($this->vote);

        return $this;
    }

    public function uploadAttachImage(UploadedFile|string $voteUploadedImageFile = null): bool
    {

        $voteImageUpload = new ModelImageUpload($this->vote);
        if ( ! empty($voteUploadedImageFile)) {
            $voteImageUpload->uploadFile(
                fileToUpload: $voteUploadedImageFile,
                requestName: 'image',
                fileType: 'vote_image',
                deleteExitingMediaFiles: false
            );
        }

        return true;
    }


    /*
    *  Update existing vote by $voteId
    *
    @var int - $voteId

    return self
    */
    public function update(int $voteId): self
    {
        $this->voteId = $voteId;

        // There are validation error messages - skip updating data
        if ($this->errorCode === HTTP_RESPONSE_UNPROCESSABLE_ENTITY and count($this->validationMessages)) {
            return $this;
        }

        $this->errorMessage = '';
        $this->errorCode    = 0;

        DB::beginTransaction();
        try {
            $this->saveData();

            return $this;
        } catch (\Exception $e) {
            DB::rollback();
            $this->errorMessage = $e->getMessage();
            $this->errorCode    = HTTP_RESPONSE_INTERNAL_SERVER_ERROR;

            return $this;
        }

        return $this;
    }

    public function getVote(bool $asResource = false): \Illuminate\Http\JsonResponse
    {
        // There are validation error messages - return error code/validation error messages
        if ($this->errorCode === HTTP_RESPONSE_UNPROCESSABLE_ENTITY and count($this->validationMessages)) {
            return response()->json(['messages' => $this->validationMessages], HTTP_RESPONSE_UNPROCESSABLE_ENTITY);
        }
        if ($asResource) {
            $this->vote->load('creator', 'voteCategory', 'voteItems');

            return response()->json(['vote' => (new VoteResource($this->vote))]);
        }

        return response()->json(['vote' => $this->vote]);
    }

    public function getValidationMessages(): array
    {
        return $this->validationMessages;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

}
