<?php

namespace App\Implementations;

use App\Interfaces\SearchManagement;
use App\Models\Page;
use App\Models\Author;
use App\Models\News;
use Artisan;
use App\Models\SearchPage;
use App\Models\SearchAuthor;
use App\Models\SearchNews;
use App\Enums\SearchManagementTables;

class AlgoliaManagement implements SearchManagement
{
    /**
     *  Truncate tables which are imported into Algolia
     *
     * @param array $listOfTables - if array empty truncate all tables, otherwise only tables in $listOfTables
     *
     * @return void
     */
    public function truncateSearchTables(array $listOfTables): void
    {
        if (in_array(SearchManagementTables::SMT_PAGES, $listOfTables)) {
            SearchPage::truncate();
        }
        if (in_array(SearchManagementTables::SMT_AUTHORS, $listOfTables)) {
            SearchAuthor::truncate();
        }
        if (in_array(SearchManagementTables::SMT_NEWS, $listOfTables)) {
            SearchNews::truncate();
        }
    }

    /**
     *  Fill tables which are imported into Algolia
     *
     * @param array $listOfTables - if array empty fill all tables, otherwise only tables in $listOfTables
     *
     * @return array of inserted rows inserted insertedPagesCount / insertedAuthorsCount / insertedNewsCount
     */
    public function fillSearchTables(array $listOfTables): array
    {
        $insertedPagesCount = 0;
        $insertedAuthorsCount = 0;
        $insertedNewsCount = 0;
        if (in_array(SearchManagementTables::SMT_PAGES, $listOfTables)) {
            $publishedPages = Page
                ::getByPublished(true)
                ->with('author')
                ->with('categories')
                ->get();
            foreach ($publishedPages as $publishedPage) {
                SearchPage::create([
                    'page_title'           => $publishedPage->title,
                    'page_slug'            => $publishedPage->slug,
                    'page_content'         => $publishedPage->content,
                    'page_content_shortly' => $publishedPage->content_shortly,
                    'page_author_name'     => $publishedPage->author['name'] ?? '',
                    'page_author_email'    => $publishedPage->author['email'] ?? '',
                    'page_price'           => $publishedPage->price,
                    'page_categories'      => $publishedPage->categories->pluck('name'),
                    'page_created_at'      => $publishedPage->created_at
                ]);
                $insertedPagesCount++;
            }
        }

        if (in_array(SearchManagementTables::SMT_AUTHORS, $listOfTables)) {
            $activeAuthors = Author
                ::getByStatus('A') // 'Active'
                ->withCount('pages')
                ->get();
            foreach ($activeAuthors as $activeAuthor) {
                SearchAuthor::create([
                    'author_name'       => $activeAuthor->name,
                    'author_email'      => $activeAuthor->email,
                    'author_first_name' => $activeAuthor->first_name,
                    'author_last_name'  => $activeAuthor->last_name,
                    'author_phone'      => $activeAuthor->phone,
                    'author_website'    => $activeAuthor->website,
                    'pages_count'       => $activeAuthor->pages_count,
                    'author_created_at' => $activeAuthor->created_at,
                ]);
                $insertedAuthorsCount++;
            }
        }

        if (in_array(SearchManagementTables::SMT_NEWS, $listOfTables)) {
            $publishedNewsRows = News
                ::getByPublished(true)
                ->with('creator')
                ->get();
            foreach ($publishedNewsRows as $publishedNews) {
                SearchNews::create([
                    'news_title'           => $publishedNews->title,
                    'news_slug'            => $publishedNews->slug,
                    'news_content'         => $publishedNews->content,
                    'news_content_shortly' => $publishedNews->content_shortly,
                    'news_author_name'     => $publishedNews->creator['name'],
                    'news_author_email'    => $publishedNews->creator['email'],
                    'news_is_homepage'     => $publishedNews->is_homepage,
                    'news_is_top'          => $publishedNews->is_top,
                    'news_created_at'      => $publishedNews->created_at,
                ]);
                $insertedNewsCount++;
            }
        }
        $retArray = [
            'insertedPagesCount' => $insertedPagesCount,
            'insertedAuthorsCount' => $insertedAuthorsCount,
            'insertedNewsCount' => $insertedNewsCount
        ];
        return $retArray;
    }

    /**
     *  Import tables which are imported into Algolia
     *
     * @param array $listOfTables - if array empty Import all tables, otherwise only tables in $listOfTables
     *
     * @return void
     */
    public function importSearchTables(array $listOfTables): void
    {
        if (in_array(SearchManagementTables::SMT_PAGES, $listOfTables)) {
            Artisan::call('scout:import "App\\\Models\\\SearchPage" ', []);
        }

        if (in_array(SearchManagementTables::SMT_AUTHORS, $listOfTables)) {
            Artisan::call('scout:import "App\\\Models\\\SearchAuthor" ', []);
        }

        if (in_array(SearchManagementTables::SMT_NEWS, $listOfTables)) {
            Artisan::call('scout:import "App\\\Models\\\SearchNews"', []);
        }
    }

    /**
     *  Search data in table/index(on Algolia) by key
     *
     * @param int $searchManagementTables - table/index in which search would be done
     *
     * @param string $key ) - search key
     *
     * @param ?int $page - Number of pages which must be returned, if null - all data are returned
     *
     * @param int $rowsPerPage - Number of rows in 1 page, if $page is not empty
     *
     * @return void
     */
    public function searchInTableByKey(int $sourceTable, string $key, ?int $page, int $rowsPerPage = 10): array
    {
        $returnedData = [];
        $totalRowsCount = 0;

        if ($sourceTable === SearchManagementTables::SMT_PAGES) {
            $totalRowsCount = SearchPage
                ::search($key)
                ->count();
            if(empty($page)) {
                $returnedData = SearchPage
                    ::search($key)
                    ->get();
            } else {
                $returnedData = SearchPage
                    ::search($key)
                    ->paginate($rowsPerPage, null, null, $page);
            }
        }

        if ($sourceTable === SearchManagementTables::SMT_AUTHORS) {
            $totalRowsCount = SearchAuthor
                ::search($key)
                ->count();
            if(empty($page)) {
                $returnedData = SearchAuthor
                    ::search($key)
                    ->get();
            } else {
                $returnedData = SearchAuthor
                    ::search($key)
                    ->paginate($rowsPerPage, null, null, $page);
            }
        }

        if ($sourceTable === SearchManagementTables::SMT_NEWS) {
            $totalRowsCount = SearchNews
                ::search($key)
                ->count();
            if(empty($page)) {
                $returnedData = SearchNews
                    ::search($key)
                    ->get();
            } else {
                $returnedData = SearchNews
                    ::search($key)
                    ->paginate($rowsPerPage, null, null, $page);
            }
        }

        return [
            'data' => $returnedData,
            'page' => $page,
            'totalRowsCount' => $totalRowsCount,
        ];
        //
    }

    /**
     *  Optimize tables/indices on Algolia
     *
     * @return void
     */
    public function doServerOptimize() {
        Artisan::call('scout:optimize', []);
    }

    /**
     *  Sync tables/indices on Algolia - usually is called after data import
     *
     * @return void
     */
    public function doServerSync() {
        Artisan::call('scout:sync', []);
    }

}
