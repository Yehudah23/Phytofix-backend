<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Cart::where('user_id', $request->user()->id)
                ->get()
                ->map(fn (Cart $cart) => $this->toView($cart))
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'product_id' => ['required', 'string', 'exists:products,id'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $product = Product::findOrFail($data['product_id']);

        $cart = Cart::updateOrCreate(
            ['user_id' => $user->id, 'product_id' => $product->id],
            [
                'name' => $product->name,
                'category' => $product->type,
                'price' => $product->price,
                'image' => $product->image,
                'quantity' => isset($data['quantity']) ? (int) $data['quantity'] : 1,
            ]
        );

        return response()->json($this->toView($cart), 201);
    }

    public function update(Request $request, Cart $cart)
    {
        abort_unless($cart->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $cart->update(['quantity' => $data['quantity']]);

        return response()->json($this->toView($cart));
    }

    public function destroy(Request $request, Cart $cart)
    {
        abort_unless($cart->user_id === $request->user()->id, 404);

        $cart->delete();

        return response()->json(['message' => 'Item removed.']);
    }

    public function clear(Request $request)
    {
        Cart::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Cart cleared.']);
    }

    public function all()
    {
        $carts = Cart::with('user:id,name,email')
            ->latest()
            ->get();

        return response()->json($carts->map(fn (Cart $c) => $this->toView($c)));
    }

    private function toView(Cart $c): array
    {
        return [
            'id' => $c->id,
            'productId' => (string) $c->product_id,
            'userId' => $c->user_id,
            'userName' => $c->user?->name,
            'userEmail' => $c->user?->email,
            'name' => $c->name,
            'category' => $c->category,
            'price' => (float) $c->price,
            'image' => $c->image,
            'quantity' => $c->quantity,
            'subtotal' => (float) $c->price * $c->quantity,
            'createdAt' => $c->created_at?->valueOf(),
        ];
    }
}