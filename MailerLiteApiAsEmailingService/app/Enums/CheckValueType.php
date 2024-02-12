<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CheckValueType extends Enum
{
    const cvtInteger =   1;
    const cvtFloat =   2;
    const cvtDate = 3;
    const cvtDateTime = 4;
    const cvtString = 5;
    const cvtBool = 6;
}
