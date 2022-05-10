<?php

namespace App\Http\Controllers\API\Admin;

use Auth;
use DB;
use Validator;

use App\library\CheckValueType;
use App\Settings;
use App\Http\Controllers\Controller;
use App\Tag;
use App\Http\Resources\TagCollection;
use App\Http\Requests\TagRequest;
use App\Facades\MyFuncsClass;


class TagController extends Controller
{
    private $requestData;
    private $page;
    private $filter_name;
    private $order_by;
    private $order_direction;


    public function __construct()
    {
        $request           = request();
        $this->requestData = $request->all();
    }

    public function filter()
    {
        if ( ! MyFuncsClass::checkUserGroup([ACCESS_ROLE_ADMIN])) {
            return response()->json(['error' => 'Unauthorized'], HTTP_RESPONSE_NOT_UNAUTHORIZED);
        }
        $backend_items_per_page = Settings::getValue('backend_items_per_page', CheckValueType::cvtInteger, 20);

        $this->page            = !empty($this->requestData['page']) ? $this->requestData['page'] : '';
        $this->filter_name     = !empty($this->requestData['filter_name']) ? $this->requestData['filter_name'] : '';
        $this->order_by        = !empty($this->requestData['order_by']) ? $this->requestData['order_by'] : 'name';
        $this->order_direction = !empty($this->requestData['order_direction']) ? $this->requestData['order_direction'] : 'asc';
        $tags = Tag
            ::getByName($this->filter_name)
            ->orderBy($this->order_by, $this->order_direction)
            ->paginate($backend_items_per_page);

        return (new TagCollection($tags));
    } // public function filter()


    public function store(TagRequest $request)
    {
        if ( ! MyFuncsClass::checkUserGroup([ACCESS_ROLE_ADMIN])) {
            return response()->json(['error' => 'Unauthorized'], HTTP_RESPONSE_NOT_UNAUTHORIZED);
        }

        $tag = new Tag;
        $tag->name = $this->requestData['name'];
        $tag->type = $this->requestData['type'];

        try {
            DB::beginTransaction();
            $tag->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage(), 'tag' => null], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        $tag = Tag::find($tag->id);
        return new TagCollection([$tag]);
    }

    public function show($id)
    {
        if ( ! MyFuncsClass::checkUserGroup([ACCESS_ROLE_ADMIN])) {
            return response()->json(['error' => 'Unauthorized'], HTTP_RESPONSE_NOT_UNAUTHORIZED);
        }
        $tag = Tag::find($id);
        if ($tag === null) {
            return response()->json([
                'message'    => 'Tag # "' . $id . '" not found!',
                'tag'       => null
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        return (new TagCollection([$tag]));
    }

    public function update(TagRequest $request, $id)
    {
        if ( ! MyFuncsClass::checkUserGroup([ACCESS_ROLE_ADMIN])) {
            return response()->json(['error' => 'Unauthorized'], HTTP_RESPONSE_NOT_UNAUTHORIZED);
        }

        $tag = Tag::find($request->id);
        if ($tag === null) {
            return response()->json([
                'message'    => 'Tag # "' . $id . '" not found!',
                'tag'       => null
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        $tag->name = $this->requestData['name'];
        $tag->type = $this->requestData['type'];

        try {
            DB::beginTransaction();
            $tag->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage(), 'tag' => null], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return new TagCollection([$tag]);
    }

    public function destroy($id)
    {
        if ( ! MyFuncsClass::checkUserGroup([ACCESS_ROLE_ADMIN])) {
            return response()->json(['error' => 'Unauthorized'], HTTP_RESPONSE_NOT_UNAUTHORIZED);
        }

        $tag = Tag::find($id);
        if ($tag === null) {
            return response()->json([
                'message'    => 'Tag # "' . $id . '" not found!',
                'tag'       => null
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        try {
            DB::beginTransaction();
            $tag->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage(), 'tag' => null], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->json(null, HTTP_RESPONSE_OK_RESOURCE_DELETED);

    }
}
