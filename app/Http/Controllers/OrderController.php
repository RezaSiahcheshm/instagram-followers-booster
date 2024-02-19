<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $userId = 1;
        // Get the IDs of orders already followed by the user
        $followedOrderIds = OrderActivity::query()->where('user_id', $userId)->pluck('order_id');
        // Retrieve pending orders that the user has not yet followed
        $pendingOrders = Order::query()
            ->where('status', 'pending')
            ->where('user_id', '!=', $userId) // Exclude orders created by the user
            ->whereNotIn('id', $followedOrderIds)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'List of orders to follow',
            'data' => $pendingOrders
        ]);
    }

    public function reward(RewardOrderRequest $request)
    {
        $user = User::query()->find(1);
        $order = Order::query()->find($request->orderId);

        DB::beginTransaction();

        try {
            $user->increment('coin', 2);

            // Create a new order activity
            OrderActivity::query()->create([
                'user_id' => $user->id,
                'order_id' => $order->id,
            ]);

            // Update order status if all activities are completed
            if ($order && $order->orderActivity()->count() >= $order->follower_count) {
                $order->update(['status' => 'completed']);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'The operation was completed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to complete the operation',
            ], 400);
        }
    }

    public function store(StoreOrderRequest $request)
    {
        $user = User::query()->findOrFail(1);

        // Check if the user has enough coins
        if ($user->coin < 10) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient coins',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $user->decrement('coin', 10);

            Order::query()->create([
                'user_id' => $user->id,
                'follower_count' => $request->followerCount,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'The order has been registered',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to register the order',
            ], 400);
        }
    }
}
