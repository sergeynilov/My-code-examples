<?php

namespace App\Enums;

enum DatetimeOutputFormat: string
{
    case AGO_FORMAT = 'ago_format';
    case AS_TEXT = 'astext';
    case AS_NUMBERS = 'numbers';
}
