<?php
namespace App\Repositories\Interfaces;

interface ItemBulkOperationsInterface
{
    public function activateItems(array $items): array;

    public function deactivateItems(array $items): array;

    public function deleteItems(array $items): array;

    public function exportItems(array $items): array;

}

