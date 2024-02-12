<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SvgIconType extends Enum
{
    public const SUBSCRIPTION = 'fa fa-envelope-open-text';
    public const CATEGORY = 'fa fa-vector-square';
    public const CITY = 'fa fa-city';

    public const ASSIGN_CATEGORY = 'fa fa-chalkboard-teacher';
    public const REVOKE_CATEGORY = 'fa fa-trash';
    public const ASSIGN_SUBSCRIPTION = 'fa fa-chalkboard-teacher';
    public const REVOKE_SUBSCRIPTION = 'fa fa-trash';

    public const PRODUCT = 'fa fa-product-hunt';
    public const INFO = 'fa fa-info';
    public const EDIT = 'fa fa-edit';
    public const CANCEL = 'fa fa-power-off';
    public const SAVE = 'fa fa-save';
    public const PLUS = 'fa fa-plus';
    public const REMOVE = 'fa fa-trash';
    public const BUG = 'fa fa-bug';
    public const WARNING = 'fa fa-exclamation-triangle';
    public const INVALID_VALIDATION = 'fa fa-exclamation-triangle';
    public const SUCCESS = 'fa fa-check';
    public const FILTER = 'fa fa-filter';
    public const REFRESH = 'fa fa-redo';
    public const SETTINGS = 'fa fa-cog';
    public const DASHBOARD = 'fa fa-tachometer-alt-slow';
    public const SUBSCRIBE_USER = 'fa fa-chalkboard-teacher';
    public const UNSUBSCRIBE_USER = 'fa fa-trash';
    public const USER = 'fa fa-user';
    public const BAD_WORD = 'fa fa-badge';
    public const UPLOAD = 'fa fa-upload';
    public const ABUSE_FLAG = 'fa fa-flag-checkered';
    public const REPORT = 'fa fa-project-diagram';
    public const GENERATE = 'fa fa-location-arrow';
}
