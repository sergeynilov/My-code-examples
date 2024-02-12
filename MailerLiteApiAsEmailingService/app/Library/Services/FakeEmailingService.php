<?php

namespace App\Library\Services;

use App\Library\Services\MlEmailingServiceInterface;

class FakeEmailingService implements MlEmailingServiceInterface  {
    /**
     * Fake of subscribing author in MailerLite in $authorsGroupTitle group
     *
     * @param array $authorData (data of user)
     *
     * @return \stdClass | bool
     */
    public function subscribeAuthorIntoAppGroup(Array $authorData): \stdClass | bool
    {
        \Log::info( ' -1 FakeEmailingService subscribeAuthorIntoAppGroup  $authorData::');
        \Log::info(print_r($authorData, true));
        $authorData['subscriber_id'] = 'NNNNNNNNN';  // Fake
        return (object)$authorData;
    }

    /**
     * Fake of removing author from MailerLite in $authorsGroupTitle group
     *
     * @param array $authorData (data of user)
     *
     * @return bool
     */
    public function removeAuthorFromAppGroup(Array $authorData): bool
    {
        \Log::info(' -1 FakeEmailingService removeAuthorFromAppGroup  $authorData::');
        \Log::info(print_r($authorData, true));
        return true;
    }

}
