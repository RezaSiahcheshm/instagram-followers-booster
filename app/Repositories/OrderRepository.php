<?php

namespace App\Repositories;

use App\Models\Order;

class OrderRepository
{
    public function listPendingOrders(int $userId)
    {
        return Order::query()
            ->where('status', 'pending')
            ->where('user_id', '!=', $userId)
            ->whereNotIn('id', function ($query) use ($userId) {
                $query->select('order_id')
                    ->from('order_activities')
                    ->where('user_id', $userId);
            })
            ->get();
    }

    public function createOrder(int $userId, int $followerCount)
    {
        Order::query()->create([
            'user_id' => $userId,
            'follower_count' => $followerCount,
        ]);
    }

    public function findByIdOrFail(int $orderId)
    {
        return Order::query()
            ->where('id', $orderId)
            ->where('status', 'pending')
            ->first() ?? throw new \Exception('Order completed or not found', 400);
    }
}