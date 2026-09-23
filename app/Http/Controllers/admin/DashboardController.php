<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'total_sales'     => (float) Order::where('status', '!=', 'cancelled')->sum('total'),
            'orders_count'    => Order::count(),
            'pending_orders'  => Order::where('status', 'pending')->count(),
            'products_count'  => Product::count(),
            'low_stock'       => Product::where('stock', '<', 10)->count(),
            'customers_count' => User::where('role', 'user')->count(),
            'recent_orders'   => Order::with('user:id,name')->latest()->take(5)->get(),
        ]);
    }
}