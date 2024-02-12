<?php

namespace App\Repositories\Interfaces;

interface AssignedItemsInterface
{
    public function getItems(int $parentId): array;

    public function assignItem(int $parentId, int $itemId): array;

    public function revokeItem(int $parentId, int $itemId): array;

}
