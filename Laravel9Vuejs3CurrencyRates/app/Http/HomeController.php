<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use App\Models\Currency;
use App\Models\Settings;
use App\Models\CMSItem;
use App\Models\Quote;
use App\Models\User;
use App\Models\ModelHasPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ContactUsCreatedNotification;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Auth;
use Session;
use DB;
use Exception;

use App\Http\Requests\ContactUsRequest;
use App\Http\Resources\QuoteResource;
use App\Http\Resources\CMSItemResource;

class HomeController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Display a vue page container with content of the main page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        return Inertia::render('Frontend/Home/Home');
    }

    public function getBlockQuote()
    {
        $request = request();
        $key     = 'mark_rutte_quote';
        $quotes  = Quote
            ::getByKey(($request->keys ?? []))
            ->getByPublished(true)
            ->get();
        if (empty($quotes)) {
            return response()->json([
                'message' => 'Quote with key "' . $key . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        return response()->json([
            'quotes' => QuoteResource::collection($quotes),
            'key'    => $key,
        ], HTTP_RESPONSE_OK);
    }

    public function getBlockCmsItem(string $key, $array_return = false)
    {
        $cMSItem = CMSItem
            ::getByKey($key)
            ->getByPublished(true)
            ->first();
        if (empty($cMSItem)) {
            return response()->json([
                'message' => 'CMS Item with key "' . $key . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }

        if ($array_return) {
            return [
                'cMSItem' => (new CMSItemResource($cMSItem))->showDefaultImage(true),
                'key'     => $key,
            ];
        }

        return response()->json([
            'cMSItem' => (new CMSItemResource($cMSItem))->showDefaultImage(true),
            'key'     => $key,
        ], HTTP_RESPONSE_OK);
    }

    /*
    * @return \Illuminate\Routing\Redirector | \Inertia\Response
    */
    public function storeContactUs(ContactUsRequest $request)
    {
        if ( ! isUserLogged()) {
            return Inertia::render('Frontend/Home/Home',
                ['message' => 'You need to login at first !'],
            );
        }

        try {
            DB::beginTransaction();
            $contactUs = ContactUs::create([
                'title'           => $request->title,
                'author_id'       => auth()->user()->id,
                'content_message' => $request->content_message,
                'ip'              => $request->ip()
            ]);

            $supportManagers = ModelHasPermission
                ::getByPermissionId(ACCESS_APP_SUPPORT_MANAGER)
                ->with('user')
                ->get();
            foreach ($supportManagers as $nextSupportManager) {
                if ($nextSupportManager->user) {
                    Notification::sendNow($nextSupportManager->user, new ContactUsCreatedNotification(
                        $request->title,
                        $request->content_message,
                        auth()->user()
                    ));
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', '');
    }


    public function getOurAuthors()
    {
        $authors = CMSItem
            ::select('author_id', DB::raw('count(*) as cms_items_count'))
            ->groupBy('author_id')
            ->orderBy('cms_items_count', 'desc')
            ->havingRaw('count(*) >= 1')
            ->get();

        foreach ($authors as $next_key => $nextCMSItem) {
            $nextAuthor = User::find($nextCMSItem['author_id']);
            if ( ! empty($nextAuthor)) {
                $authors[$next_key]->author = $nextAuthor;
                $has_image                  = false;
                foreach ($nextAuthor->getMedia(config('app.media_app_name')) as $mediaImage) {
                    if (File::exists($mediaImage->getPath())) {
                        $authors[$next_key]->author          = $nextAuthor;
                        $authors[$next_key]->media_image_url = $mediaImage->getUrl();
                        $has_image                           = true;
                        break;
                    }
                }
                if ( ! $has_image) {
                    $authors[$next_key]->media_image_url = '/images/default-avatar.png';
                }
            }
        }

        return response()->json([
            'authors' => $authors
        ], HTTP_RESPONSE_OK);
    }

    /*
    * @return \Inertia\Response
    */
    public function our_rules()
    {
        $ourRulesBlock = $this->getBlockCmsItem('our_rules_block', true);

        return Inertia::render('Frontend/CMSItem/Page', [
                'cMSItem' => (new CMSItemResource($ourRulesBlock['cMSItem']))->showDefaultImage(true),
            ]
        );
    }

    public function get_settings_value(Request $request, $key)
    {
        $value = Settings::getValue($key);

        return response()->json([
            'value' => $value,
            'key'   => $key,
        ], HTTP_RESPONSE_OK);
    }

    public function load_currency_subscription_selection(Request $request)
    {
        $currencies = Currency
            ::getByActive(Currency::ACTIVE_YES)
            ->orderBy('ordering', 'desc')
            ->select('name', 'char_code', 'id')
            ->get();

        return response()->json([
            'currencies' => $currencies,
        ], HTTP_RESPONSE_OK);
    }

    public function get_base_currency()
    {
        $value        = Settings::getValue('base_currency');
        $baseCurrency = Currency
            ::getByActive(Currency::ACTIVE_YES)
            ->getByCharCode($value)
            ->first();

        return response()->json([
            'baseCurrency' => $baseCurrency,
        ], HTTP_RESPONSE_OK);
    }

    public function perform_logout()
    {
        Session::flush();

        Auth::logout();

        return redirect(route('login'));
    }

    /*
    * @return \Inertia\Response
    */
    public function verification_notice()
    {
        return Inertia::render('Frontend/Home/VerificationNotice');
    }

}
