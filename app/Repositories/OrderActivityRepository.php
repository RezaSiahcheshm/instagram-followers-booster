<?php

namespace App\Repositories;

use App\Models\OrderActivity;

class OrderActivityRepository
{
    public function createOrderActivity(int $userId, int $orderId)
    {
        OrderActivity::create([
            'user_id' => $userId,
            'order_id' => $orderId,
        ]);
    }

    public function exists(int $userId, int $orderId): bool
    {
        return OrderActivity::query()
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->exists();
    }
}