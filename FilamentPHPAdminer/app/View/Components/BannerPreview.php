<?php

namespace App\View\Components;

use App\Exceptions\BannerGeneratorBuilderCustomException;
use App\Library\AppLocale;
use App\Library\BannerGeneratorBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\View\Component;

use App\Settings\AppSettings;
use App\Settings\BannerBuilderSettings;
use App\Models\{Banner, BannerBgimage};
use App;
use URL;

class BannerPreview extends Component
{
    protected int $bannerId;

    private $appSettings;
    private $bannerBuilderSettings;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(int $bannerId, AppSettings $appSettings, BannerBuilderSettings $bannerBuilderSettings)
    {
        $this->bannerId    = $bannerId;
        $this->appSettings = $appSettings;
        $this->bannerBuilderSettings = $bannerBuilderSettings;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        [$locales] = Arr::divide(AppLocale::getAppLocaleSelectionItems(false));
        $generateImageContents = [];
        try {
            $banner            = Banner
                ::with('bannerBgimage')
                ->findOrFail($this->bannerId);
        } catch (ModelNotFoundException $e) {
            throw new BannerGeneratorBuilderCustomException('Banner id : "' . $this->bannerId . '" not found.');
        }
        foreach ($locales as $locale) {
            // Get logo image to show in image area
            $bannerText        = $banner->getTranslation('text', $locale, false);
            $bannerDescription = $banner->getTranslation('description', $locale, false);
            $bannerLogoImageMedia = $banner->getFirstMedia(config('app.media_app_name'));
            if (empty($bannerLogoImageMedia)) {
                throw new BannerGeneratorBuilderCustomException('Banner logo image "' . $this->bannerId . '" not found.');
            }

            // Get logo of background image to show in image area
            $bannerBgimageMedia = $banner->bannerBgimage->getFirstMedia(config('app.media_app_name'));
            if (empty($bannerBgimageMedia)) {
                throw new BannerGeneratorBuilderCustomException('Invalid "bannerBgImageId" parameter provided ! ');
            }
            $textColor = $banner->bannerBgimage['text_color'];
            if (empty($textColor)) {
                throw new BannerGeneratorBuilderCustomException('Valid text color is not set ! ');
            }

            $response = (new BannerGeneratorBuilder)
                /* Set text((by default at left top corner)) with properties */
                ->setText($bannerText)
                ->setTextColor($textColor)
                ->setTextFontFilePath($this->bannerBuilderSettings->text_font_file_path)
                ->setTextXPoint($this->bannerBuilderSettings->text_x_point ?? 20)
                ->setTextYPoint($this->bannerBuilderSettings->text_y_point ?? 60)
                ->setTextSize($this->bannerBuilderSettings->text_size ?? 54)

                /* Set images - background and logo image at right corner */
                ->setBannerLogoImageFileUrl(!empty($bannerLogoImageMedia->getUrl()) ? $bannerLogoImageMedia->getUrl() : '')
                ->setBgimageFileUrl($bannerBgimageMedia->getUrl())

                /* Set description((by default at left bottom corner)) with properties */
                ->setDescription($bannerDescription)
                ->setDescriptionFontFilePath($this->bannerBuilderSettings->description_font_file_path)
                ->setDescriptionXPoint($this->bannerBuilderSettings->description_x_point ?? 20)
                ->setDescriptionYPoint($this->bannerBuilderSettings->description_y_point ?? 150)
                ->setDescriptionSize($this->bannerBuilderSettings->description_size ?? 24)
                ->generate(returnResponse: false)
                ->getResponse();
            throw_if(empty($response), new BannerGeneratorBuilderCustomException('Check if source file is valid.'));
            $contentType             = $response->getHeader('Content-Type')[0];
            $body                    = $response->getBody()->getContents();
            $base64                  = base64_encode($body);
            $imageContent            = ('data:' . $contentType . ';base64,' . $base64);
            $generateImageContents[] = [
                'imageContent' => $imageContent,
                'locale'       => $locale,
                'localeLabel'  => AppLocale::getAppLocaleLabel($locale),
            ];
        }

        return view('components.banner-preview',
            [
                'banner'                => $banner,
                'generateImageContents' => $generateImageContents,
                'siteName'              => $this->appSettings->site_name ?? '',
                'copyrightText'         => $this->appSettings->copyright_text ?? '',
                'siteVersion'           => $this->appSettings->site_version ?? '',
            ]
        );
    }
}
