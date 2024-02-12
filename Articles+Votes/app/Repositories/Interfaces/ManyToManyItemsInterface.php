<?php

namespace App\Repositories\Interfaces;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;

interface ManyToManyItemsInterface
{
    public function getToManyItems(int $id): array;

    public function storeToManyItem(int $id, int $manyItemId, array $data): JsonResponse | MessageBag;

    public function updateManyItems(int $id, int $itemId, array $data): JsonResponse | MessageBag;

    public function deleteToManyItem(int $id, int $itemId);


}
