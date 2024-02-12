<?php

namespace App\Library;

class AppLocale
{
    private static $instance;
    protected static string $currentLocale = 'en';

    public const APP_LOCALE_FLAGS_CONVERTOR = ['en' => 'GB'];
    public const APP_LOCALE_ENGLISH = 'en';
    public const APP_LOCALE_SPANISH = 'es';
    public const APP_LOCALE_UKRAINIAN = 'ua';

    protected static $appLocaleSelectionItems
        = [
            self::APP_LOCALE_ENGLISH   => 'English language',
            self::APP_LOCALE_SPANISH   => 'Lengua española',
            self::APP_LOCALE_UKRAINIAN => 'Українська мова',
        ];

    public static function getAppLocaleSelectionItems(bool $keyReturn = true): array
    {
        $resArray = [];
        foreach (self::$appLocaleSelectionItems as $key => $value) {
            if ($keyReturn) {
                $resArray[] = ['key' => $key, 'label' => $value];
            } else {
                $resArray[$key] = $value;
            }
        }

        return $resArray;
    }

    public static function getAppLocaleLabel(string $appLocale = ''): string
    {
        if ( ! empty(self::$appLocaleSelectionItems[$appLocale])) {
            return self::$appLocaleSelectionItems[$appLocale];
        }

        return self::$appLocaleSelectionItems[self::APP_LOCALE_ENGLISH];
    }

    private function __construct()
    {
        // protected constructor to prevent object creation
    }

    // as Country flag in Emoji can be different from locale - need find and use  it
    public static function getLocaleCountryFlag($locale): string
    {
        foreach (self::APP_LOCALE_FLAGS_CONVERTOR as $key => $flag) {
            if ($key === $locale) {
                return $flag;
            }
        }

        return $locale;
    }

    public static function getCurrentLocaleImageUrl(): string
    {
        $currentLocale = app()->getLocale();
        foreach (self::getAppLocaleSelectionItems(false) as $locale => $label) {
            if ($currentLocale === $locale) {
                return '/images/langs/' . $locale . '.png';
            }
        }

        return '';
    }

    public static function getLocateImages(bool $returnImageSource = true): array
    {
        $retArray = [];
        foreach (self::getAppLocaleSelectionItems(false) as $locale => $label) {
            if ($returnImageSource) {
                $retArray[$locale] = '<img src="/images/langs/' . $locale . '.png" title="' . $label . '">';
            }
        }

        return $retArray;
    }

    public static function getCurrentLocale()
    {
        if (empty(self::$currentLocale)) {
            return self::APP_LOCALE_ENGLISH;
        }

        return self::$currentLocale;
    }

    public static function getInstance(string $currentLocale = '')
    {
        if (self::$instance === null) {
            self::$instance = new AppLocale();
        }

        return self::$instance;
    }
}
