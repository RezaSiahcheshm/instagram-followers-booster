<?php

namespace App\Http\Controllers;

use App\Http\Requests\RewardOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Repositories\OrderRepository;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class OrderController extends Controller
{
    protected OrderService $orderService;

    protected OrderRepository $orderRepository;

    public function __construct(OrderService $orderService, OrderRepository $orderRepository)
    {
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
    }

    public function index()
    {
        try {
            $userId = 1;
            $pendingOrders = $this->orderRepository->listPendingOrders($userId);

            return response()->json([
                'status' => true,
                'message' => $pendingOrders->isEmpty() ? 'No orders to follow' : 'List of orders to follow',
                'data' => $pendingOrders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to show ist of orders to follow',
                'details' => config('app.debug') ? $e->getMessage() : 'For security reasons, detailed error information is only accessible in debug mode.',
            ], 400);
        }
    }

    public function reward(RewardOrderRequest $request)
    {
        try {
            $this->orderService->rewardOrder($request->orderId, 1);

            return response()->json([
                'status' => true,
                'message' => 'The operation was completed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to complete the operation',
                'details' => config('app.debug') ? $e->getMessage() : 'For security reasons, detailed error information is only accessible in debug mode.',
            ], 400);
        }
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $user = User::findOrFail(1);
            $this->orderService->storeOrder($user, $request->followerCount);

            return Response::json([
                'status' => true,
                'message' => 'The order has been registered',
            ]);
        } catch (\Exception $e) {
            return Response::json([
                'status' => false,
                'message' => 'Failed to register the order',
                'details' => config('app.debug') ? $e->getMessage() : 'For security reasons, detailed error information is only accessible in debug mode.',
            ], $e->getCode());
        }
    }
}
