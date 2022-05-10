<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Database\QueryException;
use  App\Http\Controllers\Controller;

use Carbon\Carbon;
use App\Models\Photo;
use App\Models\Nomination;
use App\Models\PhotoNomination;
use App\Models\Compilation;
use App\Models\PhotoCompilation;
use App\Models\PhotoLike;

use App\Models\Tag;

use App\Http\Requests\PhotoRequest;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class PhotosController extends Controller
{

    public function new_photos()
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.index methods');
        }

        return $this->photos_filter(true);
    }

    public function index()
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.index methods');
        }

        return $this->photos_filter();
    }

    public function get_new_photos_filter() {
        return $this->photos_filter(true);

    }

    public function photos_filter($new_photos = false)
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.index methods');
        }

        if ( !$new_photos and !empty($this->requestData['new_photos'])) {
            $new_photos = 1;
        }
        $page          = $this->requestData['page'] ?? 1;
        $rows_per_page = $this->requestData['rows_per_page'] ?? 50;
        $start         = ($page - 1) * $rows_per_page;

        $filterTags = [];
        foreach ($this->requestData as $param_name => $param_value) {
            $a = preg_split("/filter_tag_/", $param_name);
            if (count($a) == 2 and ! empty($a[1])) {
                $filterTags[] = $a[1];
            }
        }
        $filter_name           = $this->requestData['filter_name'] ?? '';
        $filter_owner_id       = $this->requestData['filter_owner_id'] ?? '';
        $filter_date_from      = $this->requestData['filter_date_from'] ?? '';
        $filter_date_till      = $this->requestData['filter_date_till'] ?? '';
        $filter_nomination_id  = $this->requestData['filter_nomination_id'] ?? '';
        $filter_compilation_id = $this->requestData['filter_compilation_id'] ?? '';

        $rows_sorted_by = $this->requestData['rows_sorted_by'] ?? null;

        if ($new_photos) {
            $sort_by_field          = 'photos.active';
            $sort_ordering          = 'asc';
            $additive_sort_by_field = 'photos.id';
            $additive_sort_ordering = 'desc';
        } else {
            $sort_by_field          = 'photos.published_at';
            $sort_ordering          = 'desc';
            $additive_sort_by_field = 'photos.published_at';
            $additive_sort_ordering = 'desc';
        }
        if ($rows_sorted_by == 'likes_count') {
            $sort_by_field          = 'photo_likes_count';
            $sort_ordering          = 'desc';
            $additive_sort_by_field = 'photo_likes_count';
            $additive_sort_ordering = 'desc';
        }
        if ($rows_sorted_by == 'nominations_count') {
            $sort_by_field          = 'photo_nominations_count';
            $sort_ordering          = 'desc';
            $additive_sort_by_field = 'photo_nominations_count';
            $additive_sort_ordering = 'desc';
        }
        if ($rows_sorted_by == 'name_asc') {
            $sort_by_field          = 'photos.name';
            $sort_ordering          = 'asc';
            $additive_sort_by_field = 'photos.name';
            $additive_sort_ordering = 'asc';
        }
        if ($rows_sorted_by == 'name_desc') {
            $sort_by_field          = 'photos.name';
            $sort_ordering          = 'desc';
            $additive_sort_by_field = 'photos.name';
            $additive_sort_ordering = 'desc';
        }
        if ($rows_sorted_by == 'published_at_date') {
            $sort_by_field          = 'photos.published_at';
            $sort_ordering          = 'desc';
            $additive_sort_by_field = 'photos.published_at';
            $additive_sort_ordering = 'desc';
        }
        if ($rows_sorted_by == 'active') {
            $sort_by_field          = 'photos.active';
            $sort_ordering          = 'asc';
            $additive_sort_by_field = 'photos.id';
            $additive_sort_ordering = 'desc';
        }

        if ($rows_sorted_by == 'creation_date') {
            $sort_by_field          = 'photos.created_at';
            $sort_ordering          = 'desc';
            $additive_sort_by_field = 'photos.created_at';
            $additive_sort_ordering = 'desc';
        }

        $nominations = Nomination
            ::orderBy('ordering', 'asc')
            ->withCount('photoNominations')
            ->get();

        $compilations = Compilation
            ::orderBy('ordering', 'asc')
            ->withCount('photoCompilations')
            ->get();

        $tags = Tag
            ::orderBy('title', 'desc')
            ->withCount('photoTags')
            ->get();

        $totalPhotosCount = Photo
            ::count();
        $photos = Photo
            ::getByName($filter_name)
            ->getByOwnerId($filter_owner_id)
            ->getByNominationId($filter_nomination_id)
            ->getByCompilationId($filter_compilation_id)
            ->getByPublishedAt($filter_date_from, ' > ')
            ->getByPublishedAt($filter_date_till . (! empty($filter_date_till) ? ' 23:59:59' : ''), ' <= ')
            ->getByTags($filterTags)
            ->getByPhotoNominationsCount($new_photos ? 0 : 1)
            ->orderBy($sort_by_field, $sort_ordering)
            ->orderBy($additive_sort_by_field, $additive_sort_ordering)
            ->offset($start)->limit($rows_per_page)
            ->with('owner')
            ->with('photoTags')
            ->with('photoTags.tag')
            ->withCount('photoLikes')
            ->with('photoNominations')
            ->withCount('photoNominations')
            ->with('photoNominations.nomination')
            ->paginate($rows_per_page, array('*'), 'page', $page)
            ->onEachSide((int)($rows_per_page / 2));

        foreach ($photos as $nextPhoto) {
            $nextPhoto->created_at_formatted   = getFormattedDateTime($nextPhoto->created_at);
            $nextPhoto->published_at_formatted = getFormattedDateTime($nextPhoto->published_at);
            $nextPhoto->active_label           = Photo::getPhotoStatusLabel($nextPhoto->active);
            $nextPhoto->edit_url               = route("photos.edit", $nextPhoto->id);
            $tags_list                         = '';
            foreach ($nextPhoto->photoTags as $nextPhotoTag) {
                if ( ! empty($nextPhotoTag['tag']['title'])) {
                    $tags_list
                        .= /*$nextPhotoTag['tag']['id'] . '=>' . */
                        $nextPhotoTag['tag']['title'] . ', ';
                }
            }

            $nextPhoto->tags_list = trimRightSubString($tags_list, ', ');

            foreach ($nextPhoto->getMedia('photo') as $mediaImage) {
                $nextPhoto->media_image_url = $mediaImage->getUrl();
                $nextPhoto->file_name       = $mediaImage->file_name;
            }

        }

        return view('admin.photo.index', [
            'totalPhotosCount'      => $totalPhotosCount,
            'nominations'           => $nominations,
            'compilations'          => $compilations,
            'tags'                  => $tags,
            'new_photos'            => $new_photos,
            'photos'                => $photos,
            'rows_per_page'         => $rows_per_page,
            'rows_sorted_by'        => $rows_sorted_by,
            'filterTags'            => $filterTags,
            'filter_owner_id'       => $filter_owner_id,
            'filter_nomination_id'  => $filter_nomination_id,
            'filter_compilation_id' => $filter_compilation_id,
            'filter_date_from'      => $filter_date_from,
            'filter_date_till'      => $filter_date_till,
            'usersArray'            => User::getUsersSelectionArray()
        ]);
    } // public function photos_filter()


    public function edit($id)
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.edit method');
        }

        $photoData = Photo
            ::getById($id)
            ->with('owner')
            ->withCount('photoLikes')
            ->first();
        if (empty($photoData)) {
            return redirect()->route('photos.index')->with('error', 'Photo not found !');
        }

        foreach ($photoData->getMedia('photo') as $mediaImage) {
            $photoData->media_image_url = $mediaImage->getUrl();
            $photoData->file_name       = $mediaImage->file_name;
            $photoData->mime_type       = $mediaImage->mime_type;
            $photoData->size_label      = getNiceFileSize($mediaImage->size);
        }

        return view('admin.photo.edit', [
            'photoData' => $photoData,
        ]);
    } // public function edit($id)

    public function update(PhotoRequest $request)
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.update method');
        }

        $id        = $request->id;
        $photoData = Photo::find($id);
        if (empty($photoData)) {
            return redirect()->route('photos.index')->with('error', 'Photo not found !');
        }

        $photoData->name              = $request->name;
        $photoData->slug              = $request->slug;
        $photoData->shown_on_homepage = $request->shown_on_homepage ? true : false;
        $photoData->active            = $request->active ? true : false;
        if ($photoData->active) {
            $photoData->published_at = Carbon::now(config('app.timezone'));
        } else {
            $photoData->published_at = null;
        }
        $photoData->updated_at = Carbon::now(config('app.timezone'));
        try {
            DB::beginTransaction();
            if ($request->shown_on_homepage) {
                $photos = Photo
                    ::getByShownOnHomepage(true)
                    ->get();
                foreach ($photos as $nextPhoto) {
                    $nextPhoto->shown_on_homepage = false;
                    $nextPhoto->save();
                }
            }
            $photoData->save();

            DB::commit();

            return redirect()->route('photos.index')->with('success_message', 'Photo updated successfully');
        } catch (QueryException $e) {
            DB::rollBack();

            return back()->with('error', 'Run time error : ' . $e->getMessage())->withInput($request->all());
        }
    } // public function update(PhotoRequest $request)

    public function get_photo_details($photo_id)
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.get_photo_details method');
        }

        $photoDetails = Photo
            ::where(with(new Photo)->getTable() . '.id', $photo_id)
            ->with('owner')
            ->with('photoTags.tag')
            ->with('photoNominations.nomination')
            ->with('photoCompilations.compilation')
            ->withCount('photoLikes')
            ->first()
            ->toArray();

        $html = view('admin.photo.photo_details',
            ['photo_id' => $photo_id, 'photoDetails' => $photoDetails])->render();

        return response()->json(['html' => $html], HTTP_RESPONSE_OK);
    } // public function get_photo_details($photo_id)


    public function destroy(Request $request, $photo_id)
    {
        if ( ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.destroy method');
        }

        $photo = Photo::find($photo_id);
        try {
            DB::beginTransaction();
            $photo->delete();

            DB::commit();

            return response()->json([], HTTP_RESPONSE_OK_RESOURCE_DELETED);
        } catch (QueryException $e) {
            DB::rollBack();

            return back()->with('error', 'Run time error : ' . $e->getMessage())->withInput($request->all());
        }
    } // public function destroy(Request $request)



    // NOMINATIONS FOR PHOTO BLOCK START
    public function get_nominations_for_photo($photo_id)
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.get_nominations_for_photo method');
        }

        $nominations = Nomination
            ::orderBy('ordering', 'asc')
            ->get()
            ->map(function ($nominationItem) use ($photo_id) {
                $nominationItem->created_at_formatted    = getFormattedDateTime($nominationItem->created_at);
                $nominationItem->active_label            = Nomination::getNominationStatusLabel($nominationItem->active);
                $nominationItem->photo_nominations_count = PhotoNomination
                    ::getByPhotoId($photo_id)
                    ->getByNominationId($nominationItem->id)
                    ->count();

                return $nominationItem;
            });

        $html = view('admin.photo.photo_nominations_selection',
            ['photo_id' => $photo_id, 'nominations' => $nominations])->render();

        return response()->json(['html' => $html], HTTP_RESPONSE_OK);
    } // public function get_nominations_for_photo($photo_id)

    public function assign_to_nomination()
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.assign_to_nomination method');
        }

        $photo_id      = $this->requestData['photo_id'] ?? null;
        $nomination_id = $this->requestData['nomination_id'] ?? null;

        $photoData = Photo::find($photo_id);
        if (empty($photoData)) {
            return response()->json(['message' => 'Photo # "' . $photo_id . '" not found !'],
                HTTP_RESPONSE_BAD_REQUEST);
        }

        $nominationData = Nomination::find($nomination_id);
        if (empty($nominationData)) {
            return response()->json(['message' => 'Nomination # "' . $nomination_id . '" not found !'],
                HTTP_RESPONSE_BAD_REQUEST);
        }

        $newPhotoNomination                = new PhotoNomination();
        $newPhotoNomination->photo_id      = $photo_id;
        $newPhotoNomination->nomination_id = $nomination_id;

        try {
            DB::beginTransaction();
            $newPhotoNomination->save();
            DB::commit();

            return response()->json(['message' => 'Nomination assigned successfully'], HTTP_RESPONSE_OK);
        } catch (QueryException $e) {
            DB::rollBack();

            return response()->json(['message' => 'Run time error : ' . $e->getMessage()], HTTP_RESPONSE_BAD_REQUEST);
        }
    } // public function assign_to_nomination()


    public function revoke_from_nomination()
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.revoke_from_nomination method');
        }

        $photo_id      = $this->requestData['photo_id'] ?? null;
        $nomination_id = $this->requestData['nomination_id'] ?? null;

        $photoData = Photo::find($photo_id);
        if (empty($photoData)) {
            return response()->json(['message' => 'Photo # "' . $photo_id . '" not found !'],
                HTTP_RESPONSE_BAD_REQUEST);
        }

        $nominationData = Nomination::find($nomination_id);
        if (empty($nominationData)) {
            return response()->json(['message' => 'Nomination # "' . $nomination_id . '" not found !'],
                HTTP_RESPONSE_BAD_REQUEST);
        }

        $photoNominations = PhotoNomination
            ::getByNominationId($nomination_id)
            ->getByPhotoId($photo_id)
            ->get();
        try {
            DB::beginTransaction();
            foreach ($photoNominations as $nextPhotoNomination) {
                $nextPhotoNomination->delete();
            }
            DB::commit();

            return response()->json(['message' => 'Nomination revoked successfully'], HTTP_RESPONSE_OK);
        } catch (QueryException $e) {
            DB::rollBack();

            return response()->json(['message' => 'Run time error : ' . $e->getMessage()], HTTP_RESPONSE_BAD_REQUEST);
        }
    } // public function revoke_from_nomination()
    // NOMINATIONS FOR PHOTO BLOCK END


    // Compilations FOR PHOTO BLOCK START
    public function get_compilations_for_photo($photo_id)
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.get_compilations_for_photo method');
        }

        $compilations = Compilation
            ::orderBy('ordering', 'asc')
            ->get()
            ->map(function ($compilationItem) use ($photo_id) {
                $compilationItem->created_at_formatted     = getFormattedDateTime($compilationItem->created_at);
                $compilationItem->active_label             = Compilation::getCompilationStatusLabel($compilationItem->active);
                $compilationItem->photo_compilations_count = PhotoCompilation
                    ::getByPhotoId($photo_id)
                    ->getByCompilationId($compilationItem->id)
                    ->count();

                return $compilationItem;
            });

        $html = view('admin.photo.photo_compilations_selection',
            ['photo_id' => $photo_id, 'compilations' => $compilations])->render();

        return response()->json(['html' => $html], HTTP_RESPONSE_OK);
    } // public function get_compilations_for_photo($photo_id)

    public function assign_to_compilation()
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.assign_to_compilation method');
        }

        $photo_id       = $this->requestData['photo_id'] ?? null;
        $compilation_id = $this->requestData['compilation_id'] ?? null;

        $photoData = Photo::find($photo_id);
        if (empty($photoData)) {
            return response()->json(['message' => 'Photo # "' . $photo_id . '" not found !'],
                HTTP_RESPONSE_BAD_REQUEST);
        }

        $compilationData = Compilation::find($compilation_id);
        if (empty($compilationData)) {
            return response()->json(['message' => 'Compilation # "' . $compilation_id . '" not found !'],
                HTTP_RESPONSE_BAD_REQUEST);
        }

        $newPhotoCompilation                 = new PhotoCompilation();
        $newPhotoCompilation->photo_id       = $photo_id;
        $newPhotoCompilation->compilation_id = $compilation_id;
        try {
            DB::beginTransaction();
            $newPhotoCompilation->save();
            DB::commit();

            return response()->json(['message' => 'Compilation assigned successfully'], HTTP_RESPONSE_OK);
        } catch (QueryException $e) {
            DB::rollBack();

            return response()->json(['message' => 'Run time error : ' . $e->getMessage()], HTTP_RESPONSE_BAD_REQUEST);
        }
    } // public function assign_to_compilation()


    public function revoke_from_compilation()
    {
        if ( ! isUserLogged() or ! Auth::user()->hasAnyPermission([PERMISSION_APP_ADMIN, PERMISSION_APP_MANAGER])) {
            Auth::logout();

            return redirect('/login')
                ->with('status', 'You have no permission to enter Photos.revoke_from_compilation method');
        }

        $photo_id       = $this->requestData['photo_id'] ?? null;
        $compilation_id = $this->requestData['compilation_id'] ?? null;

        $photoData = Photo::find($photo_id);
        if (empty($photoData)) {
            return response()->json(['message' => 'Photo # "' . $photo_id . '" not found !'],
                HTTP_RESPONSE_BAD_REQUEST);
        }

        $compilationData = Compilation::find($compilation_id);
        if (empty($compilationData)) {
            return response()->json(['message' => 'Compilation # "' . $compilation_id . '" not found !'],
                HTTP_RESPONSE_BAD_REQUEST);
        }

        $photoCompilations = PhotoCompilation
            ::getByCompilationId($compilation_id)
            ->getByPhotoId($photo_id)
            ->get();
        try {
            DB::beginTransaction();
            foreach ($photoCompilations as $nextPhotoCompilation) {
                $nextPhotoCompilation->delete();
            }
            DB::commit();

            return response()->json(['message' => 'Compilation revoked successfully'], HTTP_RESPONSE_OK);
        } catch (QueryException $e) {
            DB::rollBack();

            return response()->json(['message' => 'Run time error : ' . $e->getMessage()], HTTP_RESPONSE_BAD_REQUEST);
        }
    } // public function revoke_from_compilation()
    // Compilations FOR PHOTO BLOCK END


}
