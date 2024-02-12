<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class DatetimeOutputFormat extends Enum
{
    const dofAgoFormat =   0;
    const dofAsText =   1;
    const dofAsNumbers = 2;
}
