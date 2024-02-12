<?php

namespace App\Repositories\Interfaces;

interface ItemStatusModificationInterface
{
    public function activate(int $id): \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag;

    public function deactivate(int $id): \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag;

}
