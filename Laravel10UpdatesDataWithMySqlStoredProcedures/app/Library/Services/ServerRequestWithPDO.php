<?php

namespace App\Library\Services;

use PDO;
use App\Exceptions\ServerSpError;

class ServerRequestWithPDO
{
    public function call(string $action, array $data): int
    {
        try {
            $mysqlConfig = config('database.connections.mysql');
            $pdo = new PDO('mysql:host=' . $mysqlConfig['host'] . ';dbname=' . $mysqlConfig['database'],
                $mysqlConfig['username'], $mysqlConfig['password']);
            $stmt = $pdo->prepare($action);

            $executed = $stmt->execute($data);

            if($executed) {
                $result = $pdo->query("SELECT @out_returnCode")->fetchAll(PDO::FETCH_ASSOC);
                if(!empty($result[0]['@out_returnCode'])) {
                    return $result[0]['@out_returnCode'];
                }
            }
        } catch (\Exception|\Error $e) {
            $errorText = '';
            foreach ($stmt->errorInfo() as $errorInfoLine) {
                $errorText .= $errorInfoLine . ', ';
            }

            throw new ServerSpError($e->getMessage() . ' : '. trimRightSubString($errorText, ', '));
        }

        return 0;
    }
}
