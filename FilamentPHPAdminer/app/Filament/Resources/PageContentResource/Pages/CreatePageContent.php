<?php

namespace App\Filament\Resources\PageContentResource\Pages;

use App\Filament\Resources\PageContentResource;
use App\Library\AppLocale;
use App\Models\PageContent;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use \Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Validation\ValidationException;

class CreatePageContent extends CreateRecord
{
    protected static string $resource = PageContentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        [$locales] = Arr::divide(AppLocale::getAppLocaleSelectionItems(false));
        $titleFields          = [];
        $contentShortlyFields = [];
        $contentFields        = [];
        $defaultLocale        = AppLocale::getDefaultLocale();
        $localeTitleForSlug   = '';
        foreach ($locales as $locale) {   // Need to collect all  multi language elements/fields into 1 field
            $titleFields[$locale]          = $data['title_' . $locale];
            $contentShortlyFields[$locale] = $data['content_shortly_' . $locale];
            $contentFields[$locale]        = $data['content_' . $locale];
            if ($defaultLocale === $locale) {
                $localeTitleForSlug = $data['title_' . $locale];
            }
        }

        $data['slug']            = SlugService::createSlug(PageContent::class, 'slug', $localeTitleForSlug);
        $data['title']           = $titleFields;
        $data['content_shortly'] = $contentShortlyFields;
        $data['content']         = $contentFields;
        $data['creator_id']      = auth()->id();
        $data['auth_required']   = $data['auth_required'] ?? false;

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'New page content successfully added.';
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title("Validation Error !")
            ->danger()
            ->send();
    }

}
