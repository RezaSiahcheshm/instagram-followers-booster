<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\OrderActivityRepository;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected OrderRepository $orderRepository;
    protected OrderActivityRepository $OrderActivityRepository;

    public function __construct(OrderRepository $orderRepository, OrderActivityRepository $OrderActivityRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->OrderActivityRepository = $OrderActivityRepository;
    }

    public function rewardOrder($orderId, $userID,)
    {
        $rewardCoin = config('app.reward_coin', 2);
        $user = User::findOrFail($userID);
        $order = $this->orderRepository->findByIdOrFail($orderId);

        $orderActivityExists = $this->OrderActivityRepository->exists($userID, $orderId);
        if ($orderActivityExists) {
            throw new \Exception('Order already rewarded', 400);
        }
        DB::beginTransaction();
        try {
            $user->increment('coin', $rewardCoin);
            $this->OrderActivityRepository->createOrderActivity($user->id, $order->id);

            // Update order status if all activities are completed
            if ($order->orderActivities()->count() >= $order->follower_count) {
                $order->update(['status' => 'completed']);
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function storeOrder(User $user, int $followerCount)
    {
        $price = $followerCount * config('app.follower_price', 4);

        if ($user->coin < $price) {
            throw new \Exception('Insufficient coins', 400);
        }

        DB::beginTransaction();
        try {
            $user->decrement('coin', $price);
            $this->orderRepository->createOrder($user->id, $followerCount);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

}