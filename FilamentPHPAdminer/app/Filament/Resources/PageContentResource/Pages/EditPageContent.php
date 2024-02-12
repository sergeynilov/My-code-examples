<?php

namespace App\Filament\Resources\PageContentResource\Pages;

use App\Filament\Resources\PageContentResource;
use App\Library\AppLocale;
use App\Models\PageContent;
use Carbon\Carbon;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Kenepa\ResourceLock\Resources\Pages\Concerns\UsesResourceLock;

class EditPageContent extends EditRecord
{
    use UsesResourceLock;
    protected static string $resource = PageContentResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        [$locales] = Arr::divide(AppLocale::getAppLocaleSelectionItems(false));
        $titleFields          = [];
        $contentShortlyFields = [];
        $contentFields        = [];
        $defaultLocale = AppLocale::getDefaultLocale();
        $localeTitleForSlug = '';
        foreach ($locales as $locale) {   // Need to collect all  multi language elements/fields into 1 field
            $titleFields[$locale]          = $data['title_' . $locale];
            $contentShortlyFields[$locale] = $data['content_shortly_' . $locale];
            $contentFields[$locale]        = $data['content_' . $locale];
            if($defaultLocale === $locale) {
                $localeTitleForSlug = $data['title_' . $locale];
            }
        }
        $data['slug']           = SlugService::createSlug(PageContent::class, 'slug', $localeTitleForSlug);
        $data['title']           = $titleFields;
        $data['content_shortly'] = $contentShortlyFields;
        $data['content']         = $contentFields;
        $data['updated_at']      = Carbon::now(config('app.timezone'));
        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Page content successfully updated.';
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title("Validation Error !")
            ->danger()
            ->send();
    }

}
