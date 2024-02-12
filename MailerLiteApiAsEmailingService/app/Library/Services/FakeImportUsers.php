<?php

namespace App\Library\Services;

use App\Library\Services\MlImportUsersInterface;

class FakeImportUsers implements MlImportUsersInterface
{
    /**
     *Fake of running import for all users/subscribers in app group
     *
     * @param array $authorData
     *
     * @return \stdClass
     */
    public function runImport(array $subscribers): array
    {
        \Log::info(' FakeImportUsers runImport  $subscribers::');
        \Log::info(print_r($subscribers, true));
        return [
            'importedCount'  => 0,
            'updatedCount'   => 0,
            'unchangedCount' => 0,
        ];
    }

}
