<?php


namespace App\Library\Services;

use App\Http\Resources\PageResource;
use Carbon\Carbon;
use App\Enums\SearchDataType;
use Illuminate\Http\Request;
use  Illuminate\Http\UploadedFile;

interface PageMethodsServiceInterface
{

    /**
     * Display listing of the Page models based on provided filters
     * (filter_title, filter_published, filter_is_homepage, filter_membership_mark).
     *
     * @return \Illuminate\Http\Resources\Json\ResourceCollection
     */
    public function filter(Request $request): \Illuminate\Http\Resources\Json\ResourceCollection;

    /**
     * Store a newly created Page model in db.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(
        array $requestData,
        UploadedFile|string $pageUploadedImageFile = null,
        UploadedFile|string $pageUploadedDocumentFile = null,
        bool $makeValidation = false
    ): \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag;

    /**
     * Update existing Page model in db.
     *
     * @param \Illuminate\Http\Request $request - data to update
     * @param id $id - Page model Id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(
        array $requestData,
        UploadedFile|string $pageUploadedImageFile = null,
        UploadedFile|string $pageUploadedDocumentFile = null,
        int $id,
        bool $makeValidation = false
    ): \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag;

    /**
     * Display the specified Page model by page id.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse | PageResource
     */
    public function show(int $id) : \Illuminate\Http\JsonResponse | PageResource;


    /**
     * Publish existing Page model in db.
     *
     * @param id $id
     *
     * @return \Illuminate\Http\Response
     */
    public function publish(int $id);

    /**
     * Unpublish existing Page model in db.
     *
     * @param id $id
     *
     * @return \Illuminate\Http\Response
     */
    public function unpublish(int $id);

    /**
     * Remove the specified Page model by page id from storage.
     *
     * @param id $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id);

}


