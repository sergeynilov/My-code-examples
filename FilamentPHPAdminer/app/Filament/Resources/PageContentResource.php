<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages\CreateBannerWizard;
use App\Filament\Resources\PageContentResource\Pages;

use App\Library\AppLocale;
use App\Library\Facades\DateConv;
use App\Models\BannerCategory;
use App\Models\PageContent;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\Component;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RalphJSmit\Filament\Components\Forms\CreatedAt;
use RalphJSmit\Filament\Components\Forms\UpdatedAt;

class PageContentResource extends Resource
{
    protected static ?string $model = PageContent::class;
    protected static ?string $recordTitleAttribute = 'title';
    protected static ?string $navigationGroup = 'ContentA';

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    // http://local-filament-banners.com/images/langs/en.png
    public static function form(Form $form): Form
    {
        [$locales] = Arr::divide(AppLocale::getAppLocaleSelectionItems(false));
        $localeFields = [];
        foreach ($locales as $locale) {  // show multi language elements for all locales of the app
            $localeLabel    = AppLocale::getAppLocaleLabel($locale);
            $imgUrl         = '<img src="' . AppLocale::getLocaleImageUrlByLocale($locale) . '" title="' . $localeLabel . '" alt="' . $localeLabel . '">';
            $localeFields[] = TextInput::make('title_' . $locale)
                ->afterStateHydrated(function (TextInput $component, $state) use ($locale) {
                    $bannerModel = $component->getModelInstance();
                    if ( ! empty($bannerModel->title)) { // Fill title field only in "edit" mode
                        $component->state($bannerModel->getTranslation('title', $locale, false)); // @phpstan-ignore-line
                    }
                })
                ->lazy()
                ->afterStateUpdated(function (Closure $set, $state, Component $component) {
                    if (AppLocale::getDefaultLocale() === Str::replace('title_', '', $component->getName())) {
                        $set('slug', Str::slug($state));
                    }
                })
                ->label('Title in ' . $localeLabel)
                ->helperText($imgUrl)
                ->required()
                ->minLength(2)
                ->maxLength(255);
            $localeFields[] = RichEditor::make('content_shortly_' . $locale)
                ->afterStateHydrated(function (RichEditor $component, $state) use ($locale) {
                    $bannerModel = $component->getModelInstance();
                    if ( ! empty($bannerModel->title)) { // Fill content_shortly field only in "edit" mode
                        $component->state($bannerModel->getTranslation('content_shortly', $locale,
                            false));  // @phpstan-ignore-line
                    }
                })
                ->label('Content shortly in ' . $localeLabel)
                ->helperText($imgUrl)
                ->required()
                ->columnSpan('full');
            $localeFields[] = RichEditor::make('content_' . $locale)
                ->afterStateHydrated(function (RichEditor $component, $state) use ($locale) {
                    $bannerModel = $component->getModelInstance();
                    if ( ! empty($bannerModel->title)) { // Fill content field only in "edit" mode
                        $component->state($bannerModel->getTranslation('content', $locale,
                            false));  // @phpstan-ignore-line
                    }
                })
                ->label('Content in ' . $localeLabel)
                ->helperText($imgUrl)
                ->required()
                ->columnSpan('full');
        }

        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Locales')
                            ->schema($localeFields)
                            ->columns(2),


                        Forms\Components\Card::make()
                            ->schema([

                                Select::make('content_type')
                                    ->options(PageContent::getContentTypeSelectionItems(false))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('ordering', null);
                                        $set('auth_required', null);
//                                        $set('slug', null);
//                                        $set('creator', null);
                                    }),

                                TextInput::make('ordering')
                                    ->lazy()
                                    // ordering field is visible only when content_type = F=>FAQ,
                                    ->hidden(function (Closure $get, ?PageContent $record) {
                                        if (empty($get('content_type'))) {
                                            return false;
                                        }

                                        return $get('content_type') != 'F';
                                    })
                                    ->numeric(),
                                Forms\Components\Section::make('images')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('media')
                                            ->collection(config('app.media_app_name'))
                                            ->disableLabel(),
                                    ])
                                    // Images - is visible if only content_type === 'N'/NEWS or content_type === 'P'/Page(About/contactUs)
                                    // Images - is hidden if only content_type === 'B'/BLOG or content_type === 'F'FAQ
                                    ->hidden(function (Closure $get, ?PageContent $record) {
                                        if (empty($get('content_type'))) {
                                            return false;
                                        }

                                        return $get('content_type') === 'B' or $get('content_type') === 'F';
                                    })
                                    ->collapsible(),

                                Forms\Components\Toggle::make('published')
                                    ->label('Is published')
                                    ->helperText('If published, this page content will be hidden from all frontend pages.')
                                    ->default(false)
                                    ->hintColor('danger'),

                                Forms\Components\Toggle::make('auth_required')
                                    ->label('Is auth required')
                                    ->helperText('If auth required, this page content will be will be visible only for logged user.')
                                    ->default(false)
                                    // is visible if only content_type === 'B'/BLOG
                                    // auth_required field is visible only when content_type = 'B'/BLOG,
                                    ->hidden(function (Closure $get, ?PageContent $record) {
                                        if (empty($get('content_type'))) {
                                            return false;
                                        }

                                        return $get('content_type') != 'B';
                                    })
                                    ->hintColor('danger'),

                            ])
                            ->columns(2),


                    ])
                    ->columnSpan(['lg' => fn(?PageContent $record) => $record === null ? 3 : 2]),

                Forms\Components\Group::make()
                    ->schema([
                        // is visible if only content_type === 'B'/BLOG or content_type === 'P'/Page(About/contactUs)or content_type === 'N'/NEWS)
                        // is hidden if only content_type === 'F'/FAQ
                        Section::make(__('miscellaneous'))
                            ->description(__('You can not edit these fields'))
                            ->schema([
                                TextInput::make('id')->lazy()->disabled()->dehydrated(false)
                                    ->hidden(fn(?PageContent $record) => $record === null),
                                TextInput::make('slug')->lazy()->disabled()->dehydrated(false),

                                Forms\Components\Placeholder::make('creator')
                                    ->content(fn(?PageContent $record): ?string => $record->creator->name)
                                    ->hidden(fn(?PageContent $record) => $record === null),
                                Forms\Components\Placeholder::make('banner_clicked_counts_count')
                                    ->content(fn(?PageContent $record
                                    ): int => $record->banner_clicked_counts_count ?? 0)
                                    ->hidden(function (Closure $get, ?PageContent $record) {
                                        if (empty($get('content_type'))) {
                                            return false;
                                        }

                                        return $get('content_type') === 'F';
                                    }),

                                CreatedAt::make()->hidden(fn(?PageContent $record) => $record === null),
                                UpdatedAt::make()->hidden(fn(?PageContent $record
                                ) => $record === null or $record->updated_at === null)
                            ])
                            ->collapsible(),
                        Section::make(__('Tags/Categories'))
                            ->description(__('You can edit Tags/Categories'))
                            ->schema([
                                SpatieTagsInput::make('tags')
                                    ->type('page_content_tag')
                                    ->label(__('Tags')),

                                SpatieTagsInput::make('category')
                                    ->type('page_content_category')
                                    ->label(__('Categories')),
                            ])
                            ->collapsible()
                            ->hidden(function (Closure $get, ?PageContent $record) {
                                if (empty($get('content_type'))) {
                                    return false;
                                }

                                return $get('content_type') === 'F';
                            }),
                    ])
                    ->columnSpan(['lg' => 1]),

                Forms\Components\Section::make('Assigned banner categories')
                    ->schema([
                        Repeater::make('pageContentHasBannerCategories')
                            ->relationship()
                            ->schema([
                                TextInput::make('ordering')->lazy()->required(),
                                Select::make('banner_category_id')
                                    ->default(1)
                                    ->preload()
                                    ->options(BannerCategory::query()->pluck('name', 'id'))
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                    ])
                    ->columns(1),

            ])
            ->columns(3);

        // bannerCategories
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),

                Tables\Columns\SpatieMediaLibraryImageColumn::make('page-content-image')
                    ->label('Image')
                    ->collection(config('app.media_app_name')),

                TextColumn::make('title')->sortable()->searchable(),

                Tables\Columns\IconColumn::make('published')
                    ->label('Published')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip('If page content is not published it will not be visible on frontend pages'),

                TextColumn::make('creator.name')->sortable()->label('Creator'),
                TextColumn::make('banner_clicked_counts_count')->counts('bannerClickedCounts')->label('Clicked counts')->alignment('right'),

                TextColumn::make('ordering')->sortable()->alignment('right'),
                TextColumn::make('created_at')->dateTime(DateConv::getDateTimeFormat(\App\Enums\DatetimeOutputFormat::dofAsText))->sortable()
            ])
            ->filters([
                Filter::make('filter_published')
                    ->query(fn(Builder $query): Builder => $query->where('published', true))
                    ->indicator('Published'),
                Filter::make('filter_unpublished')
                    ->query(fn(Builder $query): Builder => $query->where('published', false))
                    ->indicator('Unpublished'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
            ],  position: Tables\Actions\Position::BeforeColumns)
            ->bulkActions(
                [
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('Publish')
                        ->label('Publish selected')
                        ->color('success')
                        ->size('lg')
                        ->icon('heroicon-o-status-offline')
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $pageContentRecord) {
                                $pageContentRecord->updated_at = Carbon::now(config('app.timezone'));;
                                $pageContentRecord->published = true;
                                $pageContentRecord->save();
                            }
                            Notification::make()
                                ->title("Selected post content(s) are published !")
                                ->success()
                                ->send();

                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('Unpublish')
                        ->label('Unpublish selected')
                        ->color('warning')
                        ->size('lg')
                        ->icon('heroicon-o-status-online')
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $pageContentRecord) {
                                $pageContentRecord->updated_at = Carbon::now(config('app.timezone'));;
                                $pageContentRecord->published = false;
                                $pageContentRecord->save();
                            }
                            Notification::make()
                                ->title("Selected post content(s) are unpublished !")
                                ->danger()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                ]
            );

    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPageContents::route('/'),
            'create' => Pages\CreatePageContent::route('/create'),
            'edit'   => Pages\EditPageContent::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        $defaultLocale = AppLocale::getDefaultLocale();
        $text          = $record->getTranslation('title', $defaultLocale, false) . ', status: ' .
                         PageContent::getPublishedLabel($record->published);

        return $text;
    }

}
