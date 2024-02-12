<?php

namespace App\Http\Controllers;

use App\Library\Services\AuthorMethodsServiceInterface;
use App\Library\Services\MlEmailingServiceInterface;
use App\Models\Author;
use Illuminate\Http\Request;
use App\Http\Resources\AuthorResource;
use App\Http\Requests\AuthorsRequest;
use Auth;

class AuthorController extends Controller
{

    /**
     * @var AuthorMethodsServiceInterface - extended CRUD methods for Author model
     */
    private AuthorMethodsServiceInterface $authorMethodsService;

    /**
     * @var MlEmailingServiceInterface - Emailing Service for all operations when email is sent to MailerLite
     */
    private MlEmailingServiceInterface $mlEmailingService;

    public function __construct(AuthorMethodsServiceInterface $authorMethodsService, MlEmailingServiceInterface $mlEmailingService)
    {
        parent::__construct();
        $this->authorMethodsService = $authorMethodsService;
        $this->mlEmailingService = $mlEmailingService;
    }

    /**
     * Display listing of the Author models based on provided filters
     * (filter_name, filter_email, filter_status, filter_membership_mark).
     *
     * @return \Illuminate\Http\Resources\Json\ResourceCollection ( AuthorResource )
     */
    public function filter(Request $request) : \Illuminate\Http\Resources\Json\ResourceCollection
    {
        return $this->authorMethodsService->filter($request);
    }

    /**
     * Store a newly created author model in db. On success new author added in subscribing group .
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag
     */
    public function store(AuthorsRequest $request): \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag
    {
        $requestData = $request->all();
        $author = $this->authorMethodsService->store(
            requestData: $requestData,
            avatarUploadedImageFile: $request->file('avatar'),
            makeValidation: true
        );

        // in case of valid JsonResponse in authorMethodsService->store ssubscribe user at MailerLiteApi
        if ($author instanceof \Illuminate\Http\JsonResponse) {
            $authorContent= $author->getContent();
            $authorContent = json_decode($authorContent);
            $assignedAuthor = $this->mlEmailingService->subscribeAuthorIntoAppGroup((array)$authorContent->author);

            $authorModel = Author::find($authorContent->author->id);
            if(!empty($assignedAuthor->subscriber_id) and !empty($authorModel) and $authorModel->subscriber_id !== $assignedAuthor->subscriber_id ) {
                $authorModel->subscriber_id = $assignedAuthor->subscriber_id;
                $authorModel->save();
            }
        }
        return $author;
    }

    /**
     * Update the specified author in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(AuthorsRequest $request, int $id)
    {
        return $this->authorMethodsService->update(
            id : $id,
            requestData : $request->all(),
            avatarUploadedImageFile: $request->file('avatar'),
            makeValidation : true
        );
    }

    /**
     * Display the specified Author model by author id.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse | AuthorResource
     */
    public function show(int $id):  \Illuminate\Http\JsonResponse | AuthorResource
    {
        return $this->authorMethodsService->show($id);
    }

    /**
     * Remove the specified Page model by author id from storage. On success removed author unsubscribed from
     * subscribing group .
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response | \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $authorData = !empty(Author::find($id) ) ? Author::find($id)->toArray() : [];
        $destroyRet = $this->authorMethodsService->destroy($id);
        if(!empty($authorData['id'])) {
            $removeAuthorRet = $this->mlEmailingService->removeAuthorFromAppGroup($authorData);
        }
        return $destroyRet;
    }
}
