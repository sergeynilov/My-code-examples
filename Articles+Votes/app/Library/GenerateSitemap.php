<?php

namespace App\Library;

use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Spatie\Sitemap\Tags\Url;
use App;
use URL as AppUrl;
use Illuminate\Support\Facades\Route;
use App\Exceptions\GenerateSitemapCustomException;
use Spatie\Sitemap\SitemapGenerator;


/*
Class for generating of site map file by specified Models
*
Example of use :
        $generateSitemap = (new GenerateSitemap())
            ->setSitemapFilename('sitemap.xml')
            ->addModel(model: \App\Models\Vote::class, priority:0.8, urlName: 'frontend.vote.show', changeFreq: 'daily', filter:"status = 'A'", modelLabel: 'Votes')
            ->addModel(model: \App\Models\Article::class, priority:0.7, urlName: 'frontend.article.show', changeFreq: "weekly", modelLabel: 'Articles')
            ->addModel(model: \App\Models\Tag::class, priority:0.5, urlName: 'frontend.mytag.show', changeFreq: "weekly", modelLabel: 'Tags')
            ->addModel(model: \App\Models\VoteCategory::class, priority:0.4, urlName: 'frontend.vote.category.show', changeFreq: "monthly", modelLabel: 'VoteCategories')
            ->generateMapFile(/*[\App\Models\Article::class, \App\Models\Vote::class, \App\Models\MyTag::class, \App\Models\VoteCategory::class]);

$resultsInfo = $generateSitemap->getResultsInfo();
*/

class GenerateSitemap
{
    protected $sitemap;
    protected array $mappingModels = [];
    protected string $sitemapFilename = 'sitemap';

    public function setSitemapFilename(string $value): self
    {
        $this->sitemapFilename = $value;

        return $this;
    }

    public function addModel(
        string $model,
        string $urlName,
        float $priority = 0.5,
        string $changeFreq = "daily",
        string $filter = '',
        string $modelLabel = ''
    ): self {
        $this->mappingModels[] = [
            'model'       => $model,
            'modelLabel'  => $modelLabel ?? Str::ucfirst(get_class($model)),
            'urlName'     => $urlName,
            'priority'    => $priority,
            'changeFreq'  => $changeFreq,
            'filter'      => $filter,
            'modelsCount' => 0
        ];

        return $this;
    }

    /*
     *
     * Generates map file by specified Models in $mappingModels array
     *
     * @param - $mappingModels - array of models to generate
     *
    */
    public function generateMapFile(): self
    {
        throw_if(count($this->mappingModels) === 0, GenerateSitemapCustomException::class,
            'There are no models defined !');

        if ( ! $this->checkValidRoutes()) {
            return $this;
        }

        // File name of generated file under site root
        $path          = base_path() . '/' . $this->sitemapFilename;
        $this->sitemap = SitemapGenerator::create(AppUrl::to("/"))
            ->getSitemap();

        foreach ($this->mappingModels as $key => $mappingModel) {
            try {
                if ( ! empty($mappingModel['filter'])) {
                    $dataRows = App::make($mappingModel['model'])
                        ->query()
                        ->whereRaw($mappingModel['filter'])
                        ->orderBy('id', 'asc')
                        ->get();
                } else {
                    $dataRows = App::make($mappingModel['model'])
                        ->query()
                        ->orderBy('id', 'asc')
                        ->get();
                }
            } catch (BindingResolutionException $e) {
                App\Library\AppCustomException::getInstance()::raiseChannelError(
                    errorMsg: 'Model # ' . $mappingModel['model'] . ' not found',
                    exceptionClass: BindingResolutionException::class,
                    file: __FILE__,
                    line: __LINE__
                );
                continue;
            }

            $this->mappingModels[$key]['modelsCount'] = count($dataRows);
            foreach ($dataRows as $dataRow) {
                $this->sitemap->add(
                    Url::create(route($this->mappingModels[$key]['urlName'], $dataRow->id))
                        ->setLastModificationDate(Carbon::createFromTimestamp(strtotime($dataRow->last_modified)))
                        ->setChangeFrequency($this->mappingModels[$key]['changeFreq'])
                        ->setPriority($this->mappingModels[$key]['priority'])
                );
            }
        }

        $this->sitemap->writeToFile($path);

        return $this;
    }

    /*
     * Check which of provided routes of map items are valid
     *
     * @return bool
     *
     * */
    protected function checkValidRoutes(): bool
    {
        foreach ($this->mappingModels as $mappingModel) {
            throw_if(! Route::has($mappingModel['urlName']), GenerateSitemapCustomException::class,
                'Url name "' . $mappingModel['urlName'] . '" for "' . $mappingModel['model'] . '" was not found !');
        }

        return true;
    }

    /*
    returns array with results info
    */
    public function getResultsInfo(): array
    {
        $retArray = ['result' => true, 'generatedSitemapFile' => base_path() . '/' . $this->sitemapFilename];
        foreach ($this->mappingModels as $mappingModel) {
            $retArray[$mappingModel['modelLabel'] . 'Count'] = $mappingModel['modelsCount'];
        }

        return $retArray;
    }

}
