<?php

namespace App\Http\Controllers;

use App\Library\Services\PageMethodsServiceInterface;
use Illuminate\Http\Request;
use App\Http\Resources\PageResource;
use Auth;

class PageController extends Controller
{

    /**
     * @var PageMethodsServiceInterface - extended CRUD methods for Page model
     */
    private PageMethodsServiceInterface $pageMethodsService;

    public function __construct(PageMethodsServiceInterface $pageMethodsService)
    {
        parent::__construct();
        $this->pageMethodsService = $pageMethodsService;
    }

    /**
     * Display listing of the Page models based on provided filters
     * (filter_title, filter_published, filter_is_homepage, filter_membership_mark).
     *
     * @return \Illuminate\Http\Resources\Json\ResourceCollection
     */
    public function filter(Request $request): \Illuminate\Http\Resources\Json\ResourceCollection
    {
        return $this->pageMethodsService->filter($request);
    }

    /**
     * Store a newly created Page model in db.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $loggedUser = Auth::user();
        $requestData = $request->all();
        if (empty($requestData['creator_id'])) {
            $requestData['creator_id'] = $loggedUser->id;
        }
        return $this->pageMethodsService->store(
            requestData: $requestData,
            pageUploadedImageFile: $request->file('image'),
            pageUploadedDocumentFile: $request->file('document'),
            makeValidation: true
        );
    }

    /**
     * Update existing Page model in db.
     *
     * @param \Illuminate\Http\Request $request - data to update
     * @param id $id - Page model Id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $loggedUser = Auth::user();
        $requestData = $request->all();
        if (empty($requestData['creator_id'])) {
            $requestData['creator_id'] = $loggedUser->id;
        }
        return $this->pageMethodsService->update(
            id : $id,
            requestData : $requestData,
            pageUploadedImageFile: $request->file('image'),
            pageUploadedDocumentFile: $request->file('document'),
            makeValidation : true
        );
    }

    /**
     * Display the specified Page model by page id.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse | PageResource
     */
    public function show(int $id): \Illuminate\Http\JsonResponse | PageResource
    {
        $data         = $this->pageMethodsService->show($id);
        return $data;
    }


    /**
     * Publish existing Page model in db.
     *
     * @param id $id
     *
     * @return \Illuminate\Http\Response
     */
    public function publish($id)
    {
        $data         = $this->pageMethodsService->publish($id);
        return $data;
    }

    /**
     * Unpublish existing Page model in db.
     *
     * @param id $id
     *
     * @return \Illuminate\Http\Response
     */
    public function unpublish($id)
    {
        $data         = $this->pageMethodsService->unpublish($id);
        return $data;
    }

    /**
     * Remove the specified Page model by page id from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response | \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        return $this->pageMethodsService->destroy($id);
    }
}
