<?php

namespace App\Enums;

enum TaskStatus: string
{
    // These values are the same as enum values in db
    case TODO = 'T';
    case DONE = 'D';

    /**
     * @return array<string, string>
     */
    public static function getStatusSelectionItems(): array
    {
        return [
            self::TODO->value => 'Todo',
            self::DONE->value => 'Done',
        ];
    }

    public static function getLabel(TaskStatus $status): string
    {
        $statusSelectionItems = self::getStatusSelectionItems();
        if (!empty($statusSelectionItems[$status->value])) {
            return $statusSelectionItems[$status->value];
        }

        return '';
    }
}
