<?php

namespace App\Repositories;

use App\Repositories\Interfaces\DBTransactionInterface;
use DB;

class DBTransaction implements DBTransactionInterface
{
    /**
     * Begin Transaction
     *
     * @return void
     */
    public function begin(): void
    {
        DB::beginTransaction();
    }

    /**
     * Commit Transaction
     *
     * @return void
     */
    public function commit(): void
    {
        DB::commit();
    }

    /**
     * Rollback Transaction
     *
     * @return void
     */
    public function rollback(): void
    {
        DB::rollback();
    }
}
