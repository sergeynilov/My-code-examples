<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class DatetimeOutputFormat extends Enum
{
    const dofAgoFormat =   'ago_format';
    const dofAsText =   'astext';
    const dofAsNumbers = 'numbers';
}
