<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSettingsImageRequest;
use App\Library\Services\CurrencyRatesFunctionalityServiceInterface;
use App\Models\Currency;
use App\Models\Settings;
use App\Models\CurrencyHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use DB;
use App;
use Exception;

use App\Http\Requests\SettingsRequest;
use Spatie\Image\Image;

class SettingsController extends Controller
{
    public function edit()
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to settings page')
                ->with('flash_type', 'error');
        }
        $settings = Settings
            ::select('*')
            ->get()
            ->pluck('value', 'name')
            ->toArray();

        $settingsSmallIconImage = [];
        $settingsSmallIcon = Settings::getSettingsList('small_icon');

        if(!empty($settingsSmallIcon[0])) {
            foreach ($settingsSmallIcon[0]->getMedia(config('app.media_app_name')) as $mediaImage) {
                if (File::exists($mediaImage->getPath())) {
                    $settingsSmallIconImage['url']       = $mediaImage->getUrl();
                    $imageInstance                  = Image::load($mediaImage->getUrl());
                    $settingsSmallIconImage['width']     = $imageInstance->getWidth();
                    $settingsSmallIconImage['height']    = $imageInstance->getHeight();
                    $settingsSmallIconImage['size']      = $mediaImage->size;
                    $settingsSmallIconImage['file_name'] = $mediaImage->file_name;
                    $settingsSmallIconImage['mime_type'] = $mediaImage->mime_type;
                    break;
                }
            }
        }

        $settingsDebugQueryTimeInMs = Settings::getByName('debug_query_time_in_ms')->first();
        return Inertia::render('Admin/Settings/Edit', [
            'settingsData' => $settings,
            'currenciesSelectionArray' => Currency::getCurrenciesSelectionArray(),
            'settingsSmallIconImage' => $settingsSmallIconImage,
            'debug_query_time_in_ms_value' => (int)$settingsDebugQueryTimeInMs->value ?? -1,
        ]);
    }

    public function update(SettingsRequest $request)
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to update settings page')
                ->with('flash_type', 'error');
        }

        try {
            DB::beginTransaction();
            $requestData = $request->all();

            foreach( $requestData as $nextSettingsKey => $nextSettingsValue ) {
                $nextSettings = Settings::getSettingsList($nextSettingsKey);

                $nextSettingsToUpdate = $nextSettings[0] ?? null;
                if( empty($nextSettingsToUpdate) ) {
                    $nextSettingsToUpdate             = new Settings;
                    $nextSettingsToUpdate->name       = $nextSettingsKey;
                } else {
                    $nextSettingsToUpdate->updated_YYYat = Carbon::now(config('app.timezone'));
                }
                $nextSettingsToUpdate->value          = $nextSettingsValue;
                $nextSettingsToUpdate->save();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect(route('admin.settings.index') )->with('flash', 'Settings  was successfully updated');
    }

    public function clearRatesHistory()
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to clear rates history in settings page')
                ->with('flash_type', 'error');
        }

        try {
            DB::beginTransaction();

            CurrencyHistory::truncate();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage(), 'settings' => null], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }
    }

    public function runCurrencyRatesImport()
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to run currency rates import in settings page')
                ->with('flash_type', 'error');
        }

        $currencyRatesFunctionality= App::make(CurrencyRatesFunctionalityServiceInterface::class);
        $retArray= $currencyRatesFunctionality->runImportCurrencyRates( from_cli: false,user_id : auth()->user()->id);
        return response()->json([ 'retArray' => $retArray], HTTP_RESPONSE_OK);
    }


    public function clearCache()
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to view laravel log in settings page')
                ->with('flash_type', 'error');
        }

        \Artisan::call('config:cache');
        \Artisan::call('route:cache');
        \Artisan::call('cache:clear');
        \Artisan::call('route:cache');
        \Artisan::call('route:clear');
        \Artisan::call('view:clear');
        \Artisan::call('clear-compiled');
        return response()->json(['error_code' => 0, 'message' => 'Cache cleared successfully', ], HTTP_RESPONSE_OK);
    }



    // LOGS BLOCK END
    public function viewLaravelLog()
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to view laravel log in settings page')
                ->with('flash_type', 'error');
        }

        $laravelLog= base_path('storage/logs/laravel.log');
        if ( file_exists($laravelLog) ) {
            $laravelLogContent= File::get($laravelLog);
            $laravelLogContent= preg_replace('/\r\n?/', "<br>", $laravelLogContent);
            return response()->json(['error_code' => 0, 'message' => '', 'text' => $laravelLogContent], HTTP_RESPONSE_OK);
        } else {
            return response()->json(['error_code' => 0, 'message' => 'laravel log file not found', 'text' => ''], HTTP_RESPONSE_NOT_FOUND);
        }
    }

    public function deleteLaravelLog()
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to delete laravel log in settings page')
                ->with('flash_type', 'error');
        }

        $laravelLog= base_path('storage/logs/laravel.log');
        if ( file_exists($laravelLog) ) {
            unlink($laravelLog);
            return response()->json(['error_code' => 0, 'message' => '', 'text' => $laravelLog], HTTP_RESPONSE_OK);
        } else {
            return response()->json(['error_code' => 0, 'message' => 'laravel log file not found', 'text' => ''], HTTP_RESPONSE_NOT_FOUND);
        }

        return response()->json(['error_code' => 0, 'message' => '', 'text' => $laravelLog], HTTP_RESPONSE_OK);
    }


    public function viewSqlTracingLog()
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to view sql tracing log in settings page')
                ->with('flash_type', 'error');
        }

        $sqlTracingLog= base_path('storage/logs/sql-tracing-.txt');
        if ( file_exists($sqlTracingLog) ) {
            $sqlTracingLogContent = str_replace('Time ', '<strong>Time </strong>', File::get($sqlTracingLog) );
            $sqlTracingLogContent= preg_replace('/\r\n?/', "<br>", $sqlTracingLogContent);
            return response()->json(['error_code' => 0, 'message' => '', 'text' => $sqlTracingLogContent], HTTP_RESPONSE_OK);
        } else {
            return response()->json(['error_code' => 0, 'message' => 'Sql tracing log file not found', 'text' => $sqlTracingLog],
                HTTP_RESPONSE_NOT_FOUND);
        }
    }

    public function deleteSqlTracingLog()
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to delete sql tracing log in settings page')
                ->with('flash_type', 'error');
        }

        $sqlTracingLog= base_path('storage/logs/sql-tracing-.txt');
        if ( file_exists($sqlTracingLog) ) {
            unlink($sqlTracingLog);
            return response()->json(['error_code' => 0, 'message' => '', 'text' => $sqlTracingLog], HTTP_RESPONSE_OK);
        } else {
            return response()->json(['error_code' => 0, 'message' => 'Sql tracing log file not found', 'text' => $sqlTracingLog],
                HTTP_RESPONSE_NOT_FOUND);
        }
    }

    // LOGS BLOCK END

    /*
     * Upload and store a selected image in the storage with ref in in db.

     * @return \Illuminate\Routing\Redirector | \Inertia\Response
   */
    public function uploadImage( UploadSettingsImageRequest $request)
    {
        if ( ! auth()->user()->can(ACCESS_APP_ADMIN_LABEL)) {
            return redirect(route('admin.dashboard.index'))
                ->with( 'flash', 'You have no access to upload image in settings page')
                ->with('flash_type', 'error');
        }

        $settingsImageUploadedFile = $request->file('image');
        $imageFilename     = checkValidFilename($request->image_filename, 255, true);
        $smallIconSettings = Settings::getSettingsList('small_icon');

        if( empty($smallIconSettings[0]) ) {
            $smallIconSettings[0]             = new Settings;
            $smallIconSettings[0]->name       = 'small_icon';
        } else {
            $smallIconSettings[0]->updated_at = Carbon::now(config('app.timezone'));
        }
        $smallIconSettings[0]->value          = $imageFilename;
        $smallIconSettings[0]->save();

        try {
            DB::beginTransaction();

            if ( ! empty($settingsImageUploadedFile)) {
                foreach ($smallIconSettings[0]->getMedia(config('app.media_app_name')) as $mediaImage) {
                    $mediaImage->delete();
                }

                $smallIconSettings[0]
                    ->addMediaFromRequest('image')
                    ->usingFileName($imageFilename)
                    ->toMediaCollection(config('app.media_app_name') );
            } // if ( ! empty($settingsImageUploadedFile)) {

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage(), 'category' => null],
                HTTP_RESPONSE_NOT_FOUND);
        }
        return Inertia::render('Admin/Settings/Edit', [
            'smallIconSettings' => $smallIconSettings[0],
        ]);
    }

}
