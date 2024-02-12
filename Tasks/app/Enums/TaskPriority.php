<?php

namespace App\Enums;

enum TaskPriority: string
{
    // These values are the same as enum values in db
    case LOW = '1';
    case NORMAL = '2';
    case HIGH = '3';
    case URGENT = '4';
    case IMMEDIATE = '5';

    /**
     * @return array<int, string>
     */
    public static function getPrioritySelectionItems(): array
    {
        return [
            self::LOW->value => 'Low',
            self::NORMAL->value => 'Normal',
            self::HIGH->value => 'High',
            self::URGENT->value => 'Urgent',
            self::IMMEDIATE->value => 'Immediate',
        ];
    }

    /* Custom Labels are used, not from case definitions */
    public static function getLabel(TaskPriority $priority): string
    {

        $prioritySelectionItems = self::getPrioritySelectionItems();
        if (!empty($prioritySelectionItems[$priority->value])) {
            return $prioritySelectionItems[$priority->value];
        }

        return '';
    }
}
