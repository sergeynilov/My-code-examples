<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Nomination;
use App\Models\PhotoLike;
use App\Models\PhotoNomination;
use App\Models\NominationPhotoCover;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use App\Models\User;


class NominationController extends Controller
{

    public function nominations_list()
    {
        return view('frontend.nomination.nominations_list', []);
    }

    public function index($slug = '', $page= 1)
    {
        $a = pregSplit('/\./', $slug);

        $request     = request();
        $requestData = $request->all();
        if(!empty($requestData['page']) and is_numeric($requestData['page'])) {
            $page= (int)$requestData['page'];
        }

        $nomination_id = null;
        if ( ! empty($a[1])) {
            $nomination_id = $a[1];
        }

        if ((int)$nomination_id == -1) {
            $nomination_id               = null;
            $defaultNomination           = new Nomination();
            $defaultNomination->id       = -1;
            $defaultNomination->title    = 'All';
            $defaultNomination->slug     = 'all';
            $defaultNomination->color    = '#ffffff';
            $defaultNomination->ordering = 0;
            $defaultNomination->active   = 1;
        } else {
            $defaultNomination = Nomination
                ::getById($nomination_id)
                ->first();
        }

        $nominated_photos_total_count = PhotoNomination
            ::getByNominationId($nomination_id)
            ->getByPhotoActive(true)
            ->count();

        $nominated_photos_per_page = (int)(config('app.nominated_photos_per_page', 15));
        $start                     = ($page - 1) * $nominated_photos_per_page;

        $nominatedPhotos = PhotoNomination
            ::getByNominationId($nomination_id)
            ->getByPhotoActive(true)
            ->offset($start)->limit($nominated_photos_per_page)
            ->with('nomination')
            ->with('photo')
            ->with('photo.owner')
            ->orderBy('photo_id', 'desc')
            ->paginate($nominated_photos_per_page, array('*'), 'page', $page)
            ->map(function ($photoNominationItem) {
                $photoItem = $photoNominationItem->photo;

                $photoNominationItem->photo_likes_count = PhotoLike
                    ::getByPhotoId($photoItem->id)
                    ->count();

                $media_image_url = '';
                $file_name       = '';
                foreach ($photoItem->getMedia('photo') as $mediaImage) {
                    $media_image_url = $mediaImage->getUrl();
                    $file_name       = $mediaImage->file_name;
                }
                $photoNominationItem->media_image_url = $media_image_url;
                $photoNominationItem->file_name       = $file_name;

                $avatar_media_image_url = '';
                $avatar_file_name       = '';
                foreach ($photoItem->owner->getMedia('avatar') as $mediaImage) {
                    $avatar_media_image_url = $mediaImage->getUrl();
                    $avatar_file_name       = $mediaImage->avatar_file_name;
                }
                $photoNominationItem->avatar_media_image_url = $avatar_media_image_url;
                $photoNominationItem->avatar_file_name       = $avatar_file_name;

                return $photoNominationItem;
            });
        $nominatedPhotosPagination = PhotoNomination
            ::getByNominationId($nomination_id)
            ->getByPhotoActive(true)
            ->offset($start)->limit($nominated_photos_per_page)
            ->with('nomination')
            ->with('photo')
            ->with('photo.owner')
            ->orderBy('photo_id', 'desc')
            ->paginate($nominated_photos_per_page, array('*'), 'page', $page);

        return view('frontend.nomination.index', [
            'nominated_photos_total_count' => $nominated_photos_total_count,
            'nominatedPhotos'     => $nominatedPhotos,
            'defaultNomination'   => $defaultNomination,
            'nominatedPhotosPagination'   => $nominatedPhotosPagination,
            'page'   => $page,
        ]);
    }


    public function get_nominated_photos($nomination_id, $current_page = 1)
    {
        $nominated_photos_per_page = (int)(config('app.nominated_photos_per_page', 15));
        $start                     = ($current_page - 1) * $nominated_photos_per_page;
        $rows_uploaded_count       = $current_page * $nominated_photos_per_page;

        if ((int)$nomination_id == -1) {
            $nomination_id = null;
        }

        $nominated_photos_total_count = PhotoNomination
            ::getByNominationId($nomination_id)
            ->getByPhotoActive(true)
            ->count();

        $nominatedPhotos = PhotoNomination
            ::getByNominationId($nomination_id)
            ->getByPhotoActive(true)
            ->offset($start)->limit($nominated_photos_per_page)
            ->with('nomination')
            ->with('photo')
            ->with('photo.owner')
            ->orderBy('photo_id', 'desc')
            ->get()
            ->map(function ($photoNominationItem) {
                $photoItem = $photoNominationItem->photo;

                $photoNominationItem->photo_likes_count = PhotoLike
                    ::getByPhotoId($photoItem->id)
                    ->count();

                $media_image_url = '';
                $file_name       = '';
                foreach ($photoItem->getMedia('photo') as $mediaImage) {
                    $media_image_url = $mediaImage->getUrl();
                    $file_name       = $mediaImage->file_name;
                }
                $photoNominationItem->media_image_url = $media_image_url;
                $photoNominationItem->file_name       = $file_name;

                $avatar_media_image_url = '';
                $avatar_file_name       = '';
                foreach ($photoItem->owner->getMedia('avatar') as $mediaImage) {
                    $avatar_media_image_url = $mediaImage->getUrl();
                    $avatar_file_name       = $mediaImage->avatar_file_name;
                }
                $photoNominationItem->avatar_media_image_url = $avatar_media_image_url;
                $photoNominationItem->avatar_file_name       = $avatar_file_name;

                return $photoNominationItem;
            });

        $html = view('frontend.nomination.nominated_photos',
            [
                'nomination_id'                => $nomination_id,
                'nominated_photos_total_count' => $nominated_photos_total_count,
                'rows_uploaded_count'          => $rows_uploaded_count,
                'nominatedPhotos'              => $nominatedPhotos
            ])->render();

        return response()->json(
            [
                'html'                         => $html,
                'nominated_photos_total_count' => $nominated_photos_total_count,
                'rows_uploaded_count'          => $rows_uploaded_count,
            ], HTTP_RESPONSE_OK);
    } // get_nominated_photos

    public function get_nominations_more_rows($current_page = 1)
    {
        $nominates_per_page  = (int)(config('app.nominates_per_page', 15));
        $start               = ($current_page - 1) * $nominates_per_page;
        $rows_uploaded_count = $current_page * $nominates_per_page;

        $active_nominations_total_count = Nomination
            ::getByActive(true)
            ->count();

        $activeNominations = Nomination
            ::getByActive(true)
            ->offset($start)->limit($nominates_per_page)
            ->orderBy('ordering', 'asc')
            ->get()
            ->map(function ($nominationItem) {


                $photoNominationIds = NominationPhotoCover
                    ::getByNominationId($nominationItem->id)
                    ->getByPhotoActive(true)
                    ->get()
                    ->pluck('photo_id')
                    ->toArray();

                if (count($photoNominationIds) > 0) {
                    $nominationItem->total_photos_count = count($photoNominationIds);


                    $photo_id  = $photoNominationIds[rand(0, count($photoNominationIds) - 1)];
                    $randPhoto = Photo
                        ::find($photo_id);

                    if ( ! empty($randPhoto)) {
                        foreach ($randPhoto->getMedia('photo') as $mediaImage) {
                            $nomination_media_image_url                 = $mediaImage->getUrl();
                            $nominationItem->nomination_media_image_url = $nomination_media_image_url;
                        }
                    }
                }

                return $nominationItem;
            });

        $html = view('frontend.nomination.nominates_more_rows',
            [
                'active_nominations_total_count' => $active_nominations_total_count,
                'rows_uploaded_count'            => $rows_uploaded_count,
                'activeNominations'              => $activeNominations
            ])->render();

        return response()->json(
            [
                'html'                           => $html,
                'active_nominations_total_count' => $active_nominations_total_count,
                'rows_uploaded_count'            => $rows_uploaded_count,
            ], HTTP_RESPONSE_OK);
    } // get_nominations_more_rows


}
