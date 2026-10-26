<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        $orders = Order::all();

        $total = $orders->count();
        $delivered = $orders->where('status', 'delivered')->count();
        $active = $total - $delivered;
        $revenue = $orders->where('status', 'delivered')->sum('total');

        return response()->json([
            'total' => $total,
            'active' => $active,
            'delivered' => $delivered,
            'revenue' => (float) $revenue,
        ]);
    }
}