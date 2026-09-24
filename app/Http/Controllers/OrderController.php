<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    // The logged-in user's own orders only
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->orders()->latest()->get()
        );
    }

    // Place an order (login required, enforced by the route group)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items'             => 'required|array|min:1',
            'items.*.name'      => 'required|string',
            'items.*.price'     => 'required|numeric|min:0',
            'items.*.quantity'  => 'required|integer|min:1',
            'phone'             => 'required|string|max:20',
            'delivery_type'     => 'required|in:pickup,delivery',
            'pickup_point'      => 'required_if:delivery_type,pickup|nullable|string|max:255',
            'delivery_address'  => 'required_if:delivery_type,delivery|nullable|string|max:500',
            'payment_method'    => 'required|in:cash,mpesa,card',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $total = collect($request->items)
            ->sum(fn ($item) => $item['price'] * $item['quantity']);

        $order = Order::create([
            'user_id'          => $request->user()->id,
            'items'            => $request->items,
            'total'            => $total,
            'phone'            => $request->phone,
            'delivery_type'    => $request->delivery_type,
            'pickup_point'     => $request->pickup_point,
            'delivery_address' => $request->delivery_address,
            'payment_method'   => $request->payment_method,
        ]);

        return response()->json($order, 201);
    }


    public function cancel(Request $request, Order $order)
{
    // Users can only cancel their own orders
    if ($order->user_id !== $request->user()->id) {
        abort(403, 'This is not your order.');
    }

    // Only allow cancelling before it is packed / on the way
    if (! in_array($order->status, ['pending', 'confirmed'])) {
        return response()->json([
            'message' => 'This order can no longer be cancelled.',
        ], 422);
    }

    $order->update(['status' => 'cancelled']);

    return response()->json($order->fresh());
}

    // Admin: every order, newest first, optionally filtered by status
    public function adminIndex(Request $request)
    {
        $query = Order::with('user:id,name,email')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    // Admin: change an order's status
    public function updateStatus(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,out_for_delivery,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->update(['status' => $request->status]);

        return response()->json($order->load('user:id,name,email'));
    }
}