<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class UploadImageRulesParameter extends Enum
{
    const UIRPV_REQUIRED = 0;
    const UIRPV_MAX_SIZE_IN_BYTES = 1;
    const UIRPV_DIMENSIONS_MAX_WIDTH = 2;
    const UIRPV_ACCEPTABLE_FILE_MIMES = 3;
}
