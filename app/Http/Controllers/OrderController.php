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
            'items'            => 'required|array|min:1',
            'items.*.name'     => 'required|string',
            'items.*.price'    => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $total = collect($request->items)
            ->sum(fn ($item) => $item['price'] * $item['quantity']);

        $order = Order::create([
            'user_id' => $request->user()->id,
            'items'   => $request->items,
            'total'   => $total,
        ]);

        return response()->json($order, 201);
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