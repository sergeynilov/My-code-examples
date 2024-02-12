<?php
namespace App\Repositories\Interfaces;

interface AssignedItemsInterface
{

    public function getItems(string $parentId): array;

    public function assignItem(string $parentId, string $itemId): array;

    public function revokeItem(string $parentId, string $itemId): array;

}

