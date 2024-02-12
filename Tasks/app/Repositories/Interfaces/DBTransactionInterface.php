<?php

namespace App\Repositories\Interfaces;

interface DBTransactionInterface
{
    /**
     * Begin Transaction
     *
     * @return void
     */
    public function begin(): void;

    /**
     * Commit Transaction
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Rollback Transaction
     *
     * @return void
     */
    public function rollback(): void;
}
