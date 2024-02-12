<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ProductBulkOperation extends Enum
{
    const PBO_ACTIVATE = 'Activate';
    const PBO_DEACTIVATE = 'Deactivate';
    const PBO_DELETE = 'Delete';
    const PBO_EXPORT = 'Export';

}
