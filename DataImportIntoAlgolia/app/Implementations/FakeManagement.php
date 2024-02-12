<?php

namespace App\Implementations;

use App\Interfaces\SearchManagement;

class FakeManagement implements SearchManagement
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
        \Log::info('truncateSearchTables FakeManagement $listOfTables::');
        \Log::info($listOfTables);
    }

    /**
     *  Fill tables which are imported into Algolia
     *
     * @param array $listOfTables - if array empty fill all tables, otherwise only tables in $listOfTables
     *
     * @return void
     */
    public function fillSearchTables(array $listOfTables): array
    {
        \Log::info(' fillSearchTables FakeManagement $listOfTables::');
        \Log::info($listOfTables);
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
        \Log::info(' importSearchTables FakeManagement $listOfTables::');
        \Log::info($listOfTables);
    }

    /**
     *  Search data in table/index(on Algolia) by key
     *
     * @param int $searchManagementTables - table/index in which search would be done
     * @param string $key) - search key
     *
     * @return void
     */
    public function searchInTableByKey(int $sourceTable, string $key, ?int $page, int $rowsPerPage = 10): array {
        \Log::info(' searchInTableByKey FakeManagement $sourceTable::');
        \Log::info($sourceTable);
        \Log::info(' searchInTableByKey FakeManagement $key::');
        \Log::info($key);
        return [];
    }

    /**
     *  Optimize tables/indices on Algolia
     *
     * @return void
     */
    public function doServerOptimize() {
        \Log::info('doServerOptimize::');
    }

    /**
     *  Sync tables/indices on Algolia - usually is called after data import
     *
     * @return void
     */
    public function doServerSync() {
        \Log::info('doServerSync::');
        Artisan::call('scout:sync', []);
    }

}
