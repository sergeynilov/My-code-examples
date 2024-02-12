<?php

namespace App\Library\Services;

interface SqlDebugServiceInterface
{
    public function writeSqlStatement(string $sqlStr, int $queryTime);

}
