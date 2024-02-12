<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class UploadImageRules extends Enum
{
    const UIR_DOCUMENT = 0;
    const UIR_PAGE_COVER_IMAGE = 1;
    const UIR_AUTHOR_AVATAR = 2;
}
