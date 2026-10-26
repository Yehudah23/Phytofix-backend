<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $email = $request->user()?->email;

        if (! $email) {
            return response()->json([]);
        }

        $orders = Order::where('customer_email', $email)->latest()->get();

        return response()->json($orders->map(fn (Order $order) => $this->toView($order)));
    }

    public function allOrders()
    {
        $orders = Order::with('user:id,name,email')
            ->latest()
            ->get();

        return response()->json($orders->map(fn (Order $order) => $this->toView($order)));
    }

    public function store(Request $request)
    {
        abort_unless($request->user() instanceof User, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:254'],
            'phone' => ['required', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        abort_unless(strcasecmp($data['email'], $request->user()->email) === 0, 403, 'Order email must match the signed-in account.');

        $order = DB::transaction(function () use ($data, $request) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy(fn (Product $product) => (string) $product->id);
            $totalCents = collect($data['items'])->sum(
                fn ($item) => (int) round((float) $products[(string) $item['product_id']]->price * 100) * $item['quantity']
            );

            $order = Order::create([
                'order_id' => 'PHYTO-'.Str::upper(Str::random(8)).'-'.random_int(100, 999),
                'user_id' => $request->user()->id,
                'customer_email' => strtolower($request->user()->email),
                'customer_name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'],
                'status' => 'placed',
                'simulated_hours' => 0,
                'total' => $totalCents / 100,
                'placed_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $product = $products[(string) $item['product_id']];
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->type,
                    'price' => $product->price,
                    'image' => $product->image,
                    'quantity' => $item['quantity'],
                ]);
            }

            $order->load(['items', 'user:id,name,email']);
            $this->notifyAdminAboutOrder($order);

            return $order;
        });

        return response()->json($this->toView($order), 201);
    }

    private function notifyAdminAboutOrder(Order $order): void
    {
        $itemCount = $order->items->sum('quantity');

        Notification::create([
            'type' => 'order_placed',
            'title' => 'New order placed',
            'body' => trim($order->customer_name.' placed an order (#'.$order->order_id.') with '.$itemCount.' item(s).'),
            'data' => [
                'order_id' => $order->order_id,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'total' => (float) $order->total,
                'item_count' => $itemCount,
            ],
            'is_read' => false,
        ]);
    }

    public function advance(Order $order)
    {
        $order->update([
            'simulated_hours' => $order->simulated_hours + 24,
        ]);

        return response()->json($this->toView($order->fresh(['items', 'user:id,name,email'])));
    }

    private function toView(Order $order): array
    {
        $order->loadMissing(['items', 'user:id,name,email']);
        $elapsed = now()->diffInMilliseconds($order->placed_at) + $order->simulated_hours * 60 * 60 * 1000;
        $window = 7 * 24 * 60 * 60 * 1000;
        $ratio = min(1, max(0, $elapsed / $window));
        $progress = (int) round($ratio * 100);
        $status = match (true) {
            $ratio >= 1 => 'delivered',
            $ratio >= 0.55 => 'in transit',
            $ratio >= 0.15 => 'processing',
            default => 'placed',
        };

        return [
            'id' => $order->order_id,
            'customerEmail' => $order->customer_email,
            'customerName' => $order->customer_name,
            'placedAt' => $order->placed_at?->valueOf(),
            'simulatedHours' => $order->simulated_hours,
            'items' => $order->items->map(fn (OrderItem $i) => [
                'productId' => $i->product_id,
                'name' => $i->name,
                'category' => $i->category,
                'price' => (float) $i->price,
                'image' => $i->image,
                'quantity' => $i->quantity,
            ]),
            'total' => (float) $order->total,
            'address' => $order->address,
            'city' => $order->city,
            'phone' => $order->phone,
            'status' => $status,
            'progress' => $progress,
            'eta' => $order->placed_at?->valueOf() + $window,
            'user' => $order->user ? [
                'id' => $order->user->id,
                'name' => $order->user->name,
                'email' => $order->user->email,
            ] : null,
        ];
    }
}