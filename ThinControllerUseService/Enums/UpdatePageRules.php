<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class UpdatePageRules extends Enum
{
    const UPR_PAGE_NOT_FOUND_BY_ID = 0;
    const UPR_LOGGED_USER_HAS_NOT_ACTIVE_STATUS_FOR_ACTION = 1;
    const UPR_LOGGED_USER_IS_NOT_CREATOR_OF_PAGE = 2;
    const ONLY_UNPUBLISHED_PAGE_UPR_CAN_UPDATED = 3;
    const UPR_LOGGED_USER_IS_NOT_ADMIN_OR_MANAGER_FOR_ACTION = 4;
    const UPR_PAGE_IS_ALREADY_PUBLISHED = 5;
    const UPR_PAGE_IS_ALREADY_UNPUBLISHED = 6;

}
