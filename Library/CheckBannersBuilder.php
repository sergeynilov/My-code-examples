<?php

namespace App\Library\Builders;

use App\Models\{Banner, BannerBgimage};
use Illuminate\Support\Facades\File;
use Illuminate\Support\{ Str };
use App\Enums\BannerValidationErrorEnum;

class CheckBannersBuilder
{
    protected bool $isDebug = false;

    protected int $minTextLength;
    protected int $maxTextLength;
    protected int $minDescriptionLength;
    protected int $maxDescriptionLength;
    protected bool $checkBannerLogoImageFileUrl = false;
    protected bool $checkBgImageFileUrl = false;
    protected bool $checkOnlyActive = false;

    protected array $results = [];

    /**
     *
     * Make validation for all banners by set of rules
     *
     * Example of use :

    $validationResult = (new CheckBannersBuilder)
    ->setMinTextLength(2)
    ->setMaxTextLength(25)
    ->setCheckBannerLogoImageFileUrl(true)
    ->setCheckBgImageFileUrl(true)
    ->setCheckOnlyActive(true)
    ->validate()
    ->getResults();
     */

    /*
    *
     * @param int $value - minimum length of text allowed in banner
     *
     * @return self
     * */
    public function setMinTextLength(int $value): self
    {
        $this->minTextLength = $value;

        return $this;
    }

    /*
    *
     * @param int $value - maximum length of text allowed in banner
     *
     * @return self
     * */
    public function setMaxTextLength(int $value): self
    {
        $this->maxTextLength = $value;

        return $this;
    }

    /*
    *
     * @param int $value - minimum length of description allowed in banner
     *
     * @return self
     * */
    public function setMinDescriptionLength(int $value): self
    {
        $this->minDescriptionLength = $value;

        return $this;
    }

    /*
    *
     * @param int $value - maximum length of description allowed in banner
     *
     * @return self
     * */
    public function setMaxDescriptionLength(int $value): self
    {
        $this->maxDescriptionLength = $value;

        return $this;
    }


    /*
     *
     * @param bool $value - check Banner Logo Image File Url exists
     *
     * @return self
     * */
    public function setCheckBannerLogoImageFileUrl(bool $value): self
    {
        $this->checkBannerLogoImageFileUrl = $value;

        return $this;
    }

    /*
     *
     * @param bool $value - check Background Image File Url exists
     *
     * @return self
     * */
    public function setCheckBgImageFileUrl(bool $value): self
    {
        $this->checkBgImageFileUrl = $value;

        return $this;
    }

    /*
     *
     * @param bool $value - check only active
     *
     * @return self
     * */
    public function setCheckOnlyActive(bool $value): self
    {
        $this->checkOnlyActive = $value;

        return $this;
    }

    /*
     * run validation
     *
     * @return | self
     * */
    public function validate(): self
    {
        $this->results            = [];
        $bannerWithoutErrorsCount = 0;
        $bannerWithErrorsCount    = 0;
        $bannersValidationErrors  = [];
        $banners                  = Banner
            ::getByActive($this->checkOnlyActive)
            ->orderBy('id', 'asc')
            ->get();
        foreach ($banners as $banner) {
            $bannerWithoutErrors = true;
            $locales = config('translatable.locales', []);
            if ( ! empty($banner->translations) ) {
                foreach ($banner['translations'] as $translation) {
                    if (!empty($this->minTextLength) and Str::length($translation['text']) < $this->minTextLength) {
                        $bannersValidationErrors[] = [
                            Str::headline(BannerValidationErrorEnum::BVEN_TEXT_IS_TOO_SHORT) =>
                                'id=' . $banner->id . '; locale = ' . $translation['locale'] .
                                '; text = ' . $translation['text'] . '; length=' . Str::length($translation['text'])
                                . ', min=' . $this->minTextLength
                        ];
                        $bannerWithoutErrors = false;
                    }
                    if ( ! empty($this->maxTextLength) and Str::length($translation['text']) > $this->maxTextLength) {
                        $bannersValidationErrors[] = [
                            Str::headline(BannerValidationErrorEnum::BVEN_TEXT_IS_TOO_LONG) =>
                                'id=' . $banner->id . '; locale = ' . $translation['locale'] .
                                '; text = ' . $translation['text'] . '; length=' . Str::length($translation['text'])
                                . ', min=' . $this->maxTextLength
                        ];
                        $bannerWithoutErrors = false;
                    }

                    //Check if $banner has not one of $locales from translatable.locales
                    foreach( $locales as $key => $locale ) {
                        if($locale === $translation['locale']) {
                            unset($locales[$key]);
                        }
                    }
                } // foreach( $banner['translations'] as $translation ) {

            } // if ( ! empty($banner['translations']) and is_array($banner['translations'])) {
            if(count($locales)) {
                foreach( $locales as $locale ) {
                    $bannersValidationErrors[] = [
                        Str::headline(BannerValidationErrorEnum::BVEN_LOCALE_IS_MISSING) =>
                        'id=' . $banner->id . '; locale = ' . $locale
                    ];
                }
            }

            if($this->checkBannerLogoImageFileUrl) {
                $bannerLogoImageMedia = $banner->getFirstMedia(config('app.media_app_name'));
                $bannerLogoImageError = false;
                if (empty($bannerLogoImageMedia) or !File::exists($bannerLogoImageMedia->getPath())) {
                    $bannerLogoImageError = true;
                }
                if($bannerLogoImageError) {
                    $bannersValidationErrors[] = [
                        Str::headline(BannerValidationErrorEnum::BVEN_BANNER_LOGO_IMAGE_NOT_FOUND) =>
                            'id=' . $banner->id . '; FilePath=' . ( $bannerLogoImageMedia ? $bannerLogoImageMedia->getPath() : '' )
                    ];
                    $bannerWithoutErrors = false;
                }
            }
            if ($bannerWithoutErrors) {
                $bannerWithoutErrorsCount++;
            } else {
                $bannerWithErrorsCount++;
            }
        }
        // foreach ($banners as $banner) {

        $bannerBgImagsValidationErrors = [];
        $bannerBgimageWithoutErrorsCount = 0;
        $bannerBgimageWithErrorsCount = 0;
        $bannerBgimages = [];
        if($this->checkBgImageFileUrl) {
            $bannerBgimages                  = BannerBgimage
                ::orderBy('id', 'asc')
                ->get();
            foreach( $bannerBgimages as $bannerBgimage ) {
                $bannerBgimageWithoutErrors = true;
                $bannerBgImageMedia = $bannerBgimage->getFirstMedia(config('app.media_app_name'));
                $bannerBgImageError = false;
                if (empty($bannerBgImageMedia) or !File::exists($bannerBgImageMedia->getPath())) {
                    $bannerBgImageError = true;
                }
                if($bannerBgImageError) {
                    $bannerBgImagsValidationErrors[] = [
                        Str::headline(BannerValidationErrorEnum::BVEN_BANNER_BACKGROUND_IMAGE_NOT_FOUND) =>
                            'id=' . $banner->id . '; FilePath=' .
                            ( $bannerBgImageMedia ? $bannerBgImageMedia->getPath() : '' )
                    ];
                    $bannerBgimageWithoutErrors = false;
                }
                if ($bannerBgimageWithoutErrors) {
                    $bannerBgimageWithoutErrorsCount++;
                } else {
                    $bannerBgimageWithErrorsCount++;
                }

            } // foreach( $bannerBgimages as $bannerBgimage ) {
        }

        $this->results = [
            'checkedBannersCount' => count($banners),
            'bannerWithoutErrorsCount' => $bannerWithoutErrorsCount,
            'bannerWithErrorsCount'    => $bannerWithErrorsCount,
            'bannersValidationErrors'  => $bannersValidationErrors,

            'checkedBannerBgimagesCount' => count($bannerBgimages),
            'bannerBgimageWithoutErrorsCount' => $bannerBgimageWithoutErrorsCount,
            'bannerBgimageWithErrorsCount' => $bannerBgimageWithErrorsCount,
            'bannerBgImagsValidationErrors'  => $bannerBgImagsValidationErrors,
        ];

        return $this;
    }

    /*
     * @return array results of validation
     * */
    public function getResults(): array
    {
        return $this->results;
    }


}
