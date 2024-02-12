<?php

namespace App\Library\Services;

use App\Enums\CheckValueType;
use App\Library\Services\SqlDebugServiceInterface;
use App\Models\Settings;
use Exception;
use Session;

class SqlDebug implements SqlDebugServiceInterface
{
    private $debugQueryTimeInMs = 0; // // write ALL queries to file

    public function writeSqlStatement(string $sqlStr, int $queryTime = null)
    {
        if ($this->debugQueryTimeInMs === 0) { // write ALL queries to file
            $this->writeSqlToLog($sqlStr, 'Time(in ms) ' . $queryTime . ' : ' . PHP_EOL);
            $this->writeSqlToLog("");
            $this->writeSqlToLog("");
        }

        if ($this->debugQueryTimeInMs > 0) { // write ONLY queries execution time $queryTime takes more debugQueryTimeInMs)
            if (!empty($queryTime) and $this->debugQueryTimeInMs <= $queryTime) { //
                $this->writeSqlToLog('PASSED debugQueryTimeInMs ( ' . $this->debugQueryTimeInMs . ' ) parameter : ');
                $this->writeSqlToLog($sqlStr, 'Time(in ms) ' . $queryTime . ' : ' . PHP_EOL);
                $this->writeSqlToLog('');
                $this->writeSqlToLog('');
            }
        }
    } // public static function runSendUserCurrencySubscriptions(bool $from_cli = false)

    private function formatSql(string $sql): string
    {
        $spaceChar = '  ';
        $boldStart = '';
        $boldEnd   = '';
        $breakLine = PHP_EOL;
        $sql       = ' ' . $sql . ' ';
        $leftCond  = '~\b(?<![%\'])';
        $rightCond = '(?![%\'])\b~i';

        $sql = preg_replace($leftCond . "insert[\s]+into" . $rightCond,
            $spaceChar . $spaceChar . $boldStart . "INSERT INTO" . $boldEnd, $sql);
        $sql = preg_replace($leftCond . "insert" . $rightCond, $spaceChar . $boldStart . "INSERT" . $boldEnd,
            $sql);
        $sql = preg_replace($leftCond . "delete" . $rightCond, $spaceChar . $boldStart . "DELETE" . $boldEnd,
            $sql);
        $sql = preg_replace($leftCond . "values" . $rightCond,
            $breakLine . $spaceChar . $spaceChar . $boldStart . "VALUES" . $boldEnd, $sql);
        $sql = preg_replace($leftCond . "update" . $rightCond, $spaceChar . $boldStart . "UPDATE" . $boldEnd,
            $sql);
        $sql = preg_replace($leftCond . "inner[\s]+join" . $rightCond,
            $breakLine . $spaceChar . $spaceChar . $boldStart . "INNER JOIN" . $boldEnd,
            $sql);
        $sql = preg_replace($leftCond . "straight[\s]+join" . $rightCond,
            $breakLine . $spaceChar . $spaceChar . $boldStart . "STRAIGHT_JOIN" . $boldEnd, $sql);
        $sql = preg_replace($leftCond . "left[\s]+join" . $rightCond,
            $breakLine . $spaceChar . $spaceChar . $boldStart . "LEFT JOIN" . $boldEnd,
            $sql);
        $sql = preg_replace($leftCond . "select" . $rightCond, $spaceChar . $boldStart . "SELECT" . $boldEnd,
            $sql);
        $sql = preg_replace($leftCond . "from" . $rightCond,
            $breakLine . $spaceChar . $spaceChar . $boldStart . "FROM" . $boldEnd, $sql);
        $sql = preg_replace($leftCond . "where" . $rightCond,
            $breakLine . $spaceChar . $spaceChar . $boldStart . "WHERE" . $boldEnd, $sql);
        $sql = preg_replace($leftCond . "group by" . $rightCond,
            $breakLine . $spaceChar . $spaceChar . "GROUP BY" . $boldEnd, $sql);
        $sql = preg_replace($leftCond . "having" . $rightCond,
            $breakLine . $spaceChar . $boldStart . "HAVING" . $boldEnd, $sql);
        $sql = preg_replace($leftCond . "order[\s]+by" . $rightCond,
            $breakLine . $spaceChar . $spaceChar . $boldStart . "ORDER BY" . $boldEnd, $sql);
        $sql = preg_replace($leftCond . "and" . $rightCond,
            $spaceChar . $spaceChar . $boldStart . "AND" . $boldEnd, $sql);
        $sql = preg_replace($leftCond . "or" . $rightCond,
            $spaceChar . $spaceChar . $boldStart . "OR" . $boldEnd, $sql);
        $sql = preg_replace($leftCond . "as" . $rightCond,
            $spaceChar . $spaceChar . $boldStart . "AS" . $boldEnd, $sql);
        $sql = preg_replace($leftCond . "exists" . $rightCond,
            $breakLine . $spaceChar . $spaceChar . $boldStart . "EXISTS" . $boldEnd, $sql);

        return $sql;
    }

    private function writeSqlToLog($contents, string $description = '', string $fileName = ''): bool
    {
        $debug = config('app.debug');
        if ( ! $debug) {
            return false;
        }

        if (empty($description)) {
            $description = '';
        }
        try {
            if (empty($fileName)) {
                $fileName = storage_path() . '/logs/sql-tracing-' . '.txt';
            }
            $fd = fopen($fileName, "a+");
            if (is_array($contents)) {
                $contents = print_r($contents, true);
            }
            fwrite($fd, $description . $this->formatSql($contents) . chr(13));
            fclose($fd);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

}
