<?php

namespace App\Library\Services;

interface MlEmailingServiceInterface
{

    public function subscribeAuthorIntoAppGroup(Array $authorData): \stdClass | bool;

    public function removeAuthorFromAppGroup(Array $authorData): bool;

}


