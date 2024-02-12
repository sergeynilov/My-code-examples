<?php

namespace App\Repositories\Interfaces;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\MessageBag;

interface ChildItemsInterface
{
    public function getChildItems(int $parentId): array;

    public function storeChildItem(int $parentId, array $data, bool $makeValidation = false): JsonResponse | MessageBag;

    public function updateChildItem(int $parentId, int $itemId, array $data, bool $makeValidation = false): JsonResponse | MessageBag;

    public function deleteChildItem(int $parentId, int $itemId): Response|JsonResponse|MessageBag;
}
