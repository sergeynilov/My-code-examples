<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencyHistory;
use App\Models\Settings;
use App\Models\Currency;
use App\Enums\CheckValueType;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Auth;
use DB;
use App\Http\Resources\CurrencyResource;
use App\Http\Requests\CurrencyRequest;
use App\Http\Requests\CurrencyDescriptionRequest;
use App\Http\Requests\UploadCurrencyImageRequest;

class CurrencyController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Display a vue page container for listing of the Currencies.
     *
     * @return \Illuminate\Routing\Redirector | \Inertia\Response
     */
    public function index()
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to currencies listing page')
                ->with('flash_type', 'error');
        }

        return Inertia::render('Admin/Currencies/Index', []);
    }

    /**
     * Returns array of Currencies by provided filters(filter_name), page number(page),
     * ordering(order_by/order_direction)
     *
     * @return \Illuminate\Http\JsonResponse | \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function filter()
    {
        $page = 1;
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return response()->json([
                'message' => 'You have no access to this page' ], HTTP_RESPONSE_UNAUTHORIZED);
        }

        $request                = request();
        $filterName     = $request->filter_name ?? '';
        $orderBy        = $request->order_by ?? 'ordering';
        $orderDirection = $request->order_direction ?? 'desc';
        $backendItemsPerPage = Settings::getValue('backend_items_per_page', CheckValueType::cvtInteger, 20);

        $currencies = Currency
            ::getByName($filterName)
            ->orderBy($orderBy, $orderDirection)
            ->paginate($backendItemsPerPage, array('*'), 'page', $page);
        return (CurrencyResource::customCollection($currencies, false));
    }

    /**
     * Show the vue form for creating a new Currency.
     *
     * @return \Illuminate\Routing\Redirector | \Illuminate\Routing\Redirector | \Inertia\Response
     */
    public function create()
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to currencies listing page')
                ->with('flash_type', 'error');
        }

        return Inertia::render('Admin/Currencies/Create', [
            'currency' => (new CurrencyResource(new Currency))->showDefaultImage(false),
        ]);
    }

    /**
     * Store a newly created currency in db.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CurrencyRequest $request)
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to currencies listing page')
                ->with('flash_type', 'error');
        }

        try {
            DB::beginTransaction();
            $currency = Currency::create([
                'name'      => $request->name,
                'num_code'  => $request->num_code,
                'char_code' => $request->char_code,
                'bgcolor'   => $request->bgcolor,
                'color'     => $request->color,
                'is_top'    => $request->is_top ? true : false,
                'ordering'  => $request->ordering ?? Currency::max('ordering') + 1,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect(route('admin.currencies.edit', $currency->id))
            ->with('flash','New currency was successfully added')
            ->with('flash_type', 'success');
    }

    /**
     * Show the vue form for editing the specified Currency.
     *
     * @param int $currencyId
     *
     * @return \Illuminate\Routing\Redirector | \Inertia\Response
     */
    public function edit(int $currencyId)
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with('flash', 'You have no access to currency edit method')
                ->with('flash_type', 'error');
        }

        $currency = Currency
            ::getById($currencyId)
            ->first();
        if ($currency == null) {
            return redirect(route('admin.dashboard.index'))
                ->with('flash', 'Currency was not found')
                ->with('flash_type', 'error');
        }

        $minCurrencyHistoryDay = CurrencyHistory
            ::getByCurrencyId($currencyId)
            ->min('day');
        return Inertia::render('Admin/Currencies/Edit', [
            'currency' => (new CurrencyResource($currency))->showDefaultImage(false),
            'minCurrencyHistoryDay' => $minCurrencyHistoryDay,
        ]);
    }

    public function update(CurrencyRequest $request, int $currencyId)
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to currencies listing page')
                ->with('flash_type', 'error');
        }

        $currency = Currency
            ::getById($currencyId)
            ->first();
        try {
            DB::beginTransaction();

            $currency->name       = $request->name;
            $currency->num_code   = $request->num_code;
            $currency->char_code  = $request->char_code;
            $currency->bgcolor    = $request->bgcolor;
            $currency->color      = $request->color;
            $currency->is_top     = $request->is_top ? true : false;
            $currency->ordering   = $request->ordering ?? Currency::max('ordering') + 1;
            $currency->updated_at = Carbon::now(config('app.timezone'));
            $currency->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect(route('admin.currencies.index'))
            ->with('flash', 'Currency was successfully updated')
            ->with('flash_type', 'success');
    }

    public function destroy(int $currencyId)
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to currencies listing page')
                ->with('flash_type', 'error');
        }

        $currency = Currency::find($currencyId);
        if ($currency == null) {
            return response()->json([
                'message' => 'Currency # "' . $currencyId . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }
        try {
            DB::beginTransaction();
            $currency->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect(route('admin.currencies.index'))
            ->with('flash', 'You have deleted currency successfully')
            ->with('flash_type', 'success');
    }

    /**
     * Upload and store a selected image in the storage with ref in in db.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function uploadImage( UploadCurrencyImageRequest $request)
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to upload image')
                ->with('flash_type', 'error');
        }

        $currency = Currency::find($request->currency_id);
        if ($currency === null) {
            return response()->json([
                'message'  => 'Currency # "' . $request->currency_id . '" not found',
                'currency' => null
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        $currencyImageUploadedFile = $request->file('image');
        $imageFilename     = checkValidFilename($request->image_filename, 255, true);

        try {
            DB::beginTransaction();
            if ( ! empty($currencyImageUploadedFile)) {
                foreach ($currency->getMedia(config('app.media_app_name')) as $mediaImage) {
                    $mediaImage->delete();
                }
                $currency
                    ->addMediaFromRequest('image')
                    ->usingFileName($imageFilename)
                    ->toMediaCollection(config('app.media_app_name') );

                $currency->updated_at = Carbon::now(config('app.timezone'));
                $currency->save();
            } // if ( ! empty($currencyImageUploadedFile)) {
            DB::commit();
        } catch (Exception $e) {//
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'category' => null],
                HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return Inertia::render('Admin/Currencies/Edit', [
            'currency' => (new CurrencyResource($currency))->showDefaultImage(false),
        ]);
    }

    public function activate(Request $request, int $currencyId)
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to currencies activate method')
                ->with('flash_type', 'error');
        }

        $currency = Currency
            ::getById($currencyId)
            ->first();
        if(empty($currency)) {
            return response()->json([
                'message' => 'Currency # "' . $currencyId . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        try {
            DB::beginTransaction();
            $currency->active     = 1;
            $currency->updated_at = Carbon::now(config('app.timezone'));
            $currency->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'currency' => $currency,
            'message' => 'Currency was successfully activated',
        ], HTTP_RESPONSE_OK);
    }

    public function deactivate(Request $request, int $currencyId)
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to currencies deactivate method')
                ->with('flash_type', 'error');
        }

        $currency = Currency
            ::getById($currencyId)
            ->first();
        if(empty($currency)) {
            return response()->json([
                'message' => 'Currency # "' . $currencyId . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        try {
            DB::beginTransaction();
            $currency->active     = 0;
            $currency->updated_at = Carbon::now(config('app.timezone'));
            $currency->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'currency' => $currency,
            'message' => 'Currency was successfully deactivated',
        ], HTTP_RESPONSE_OK);
    }

    public function descriptionSave(CurrencyDescriptionRequest $request, int $currencyId)
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to currency descriptionSave method')
                ->with('flash_type', 'error');
        }

        $currency = Currency
            ::getById($currencyId)
            ->first();

        try {
            DB::beginTransaction();
            $currency->description       = $request->description;
            $currency->updated_at = Carbon::now(config('app.timezone'));
            $currency->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['message' => $e->getMessage()]);
        }
        return redirect(route('admin.currencies.edit', $currency->id))
            ->with('flash', 'New description was successfully updated')
            ->with('flash_type', 'success');
    }

}
