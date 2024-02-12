<?php

namespace App\Interfaces;

use App\Enums\SearchManagementTables;

interface SearchManagement
{
    /**
     *  Truncate tables which are imported into Algolia
     *
     * @param array $listOfTables - if array empty truncate all tables, otherwise only tables in $listOfTables
     *
     * @return void
     */
    public function truncateSearchTables(array $listOfTables): void;

    /**
     *  Fill tables which are imported into Algolia
     *
     * @param array $listOfTables - if array empty fill all tables, otherwise only tables in $listOfTables
     *
     * @return array of inserted rows inserted insertedPagesCount / insertedAuthorsCount / insertedNewsCount
     */
    public function fillSearchTables(array $listOfTables): array;

    /**
     *  Import tables which are imported into Algolia
     *
     * @param array $listOfTables - if array empty Import all tables, otherwise only tables in $listOfTables
     *
     * @return void
     */
    public function importSearchTables(array $listOfTables): void;

    /**
     *  Search data in table/index(on Algolia) by key
     *
     * @param int $sourceTable - table/index in which search would be done
     * @param string $key) - search key
     *
     * @return void
     */
    public function searchInTableByKey(int $sourceTable, string $key, ?int $page, int $rowsPerPage = 10): array;

    /**
     *  Optimize tables/indices on Algolia
     *
     * @return void
     */
    public function doServerOptimize();

    /**
     *  Sync tables/indices on Algolia - usually is called after data import
     *
     * @return void
     */
    public function doServerSync();

}
