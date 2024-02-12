<?php

namespace App\Filament\Widgets;

use App\Library\AppLocale;
use App\Models\Banner;
use App\Models\BannerCategory;
use App\Models\BannerClickedCount;
use App\Models\PageContent;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Filament\Forms\Components\DatePicker;

class BannerClickedCountsChart extends ApexChartWidget
{

    protected int | string | array $columnSpan = 'full';

    /**
     * Chart Id
     *
     * @var string
     */
    protected static string $chartId = 'bannerClickedCountsChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Banner clicked counts statistics ';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $dateStart = !empty($this->filterFormData['date_start']) ? Carbon::parse($this->filterFormData['date_start']) : null;
        $dateEnd = !empty($this->filterFormData['date_end']) ? Carbon::parse($this->filterFormData['date_end']) : null;
        $pageContentId = !empty($this->filterFormData['page_content_id']) ? $this->filterFormData['page_content_id'] : null;
        $locale = !empty($this->filterFormData['locale']) ? $this->filterFormData['locale'] : null;

        $bannerCategories = BannerCategory
            ::leftJoin((new Banner)->getTable(), (new Banner)->getTable().'.banner_category_id', '=', (new BannerCategory)->getTable() . '.id')
            ->orderBy('name', 'asc')
            ->addSelect(['banner_clicked_counts_count' => BannerClickedCount
                ::selectRaw('count(*)')
                ->whereColumn('banners.banner_category_id', 'banner_categories.id')
                ->whereColumn('banner_clicked_counts.banner_id', 'banners.id')
                ->whereRaw("banners.active = true ")
                ->getByCreatedAt($dateStart->startOfDay(), '>=')
                ->getByCreatedAt($dateEnd->endOfDay(), '<=')
                ->getByPageContentId($pageContentId)
                ->getByLocale($locale)
            ])
            ->get();

        $seriesData = [];
        $labelsData = [];
        foreach( $bannerCategories as $bannerCategory ) {
            $seriesData[] = $bannerCategory->getAttribute('banner_clicked_counts_count');
            $labelsData[] = $bannerCategory->name;
        }
        return [
            'chart' => [
                'type' => 'donut',
                'height' => 500,
            ],
            'series' => $seriesData,
            'labels' => $labelsData,
            'legend' => [
                'labels' => [
                    'colors' => '#9ca3af',
                    'fontWeight' => 700,
                ],
            ],
        ];
    }

    protected function getFormSchema(): array
    {
        $locales = AppLocale::getAppLocaleSelectionItems(true);
        $localeOptions = [];
         foreach( $locales as $value ) {
             $localeOptions[$value['key']] = $value['label'];
         }
        return [
            DatePicker::make('date_start')
                ->default(now()->subMonth()),
            DatePicker::make('date_end')
                ->default(now()),

            Select::make('locale')
                ->preload()
                ->options($localeOptions)
                ->label('Select locale'),
            Select::make('page_content_id')
                ->preload()
                ->options(PageContent::query()->pluck('title', 'id'))
                ->label('Select page content'),
        ];
    }
}
