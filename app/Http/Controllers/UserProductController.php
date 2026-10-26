<?php

namespace App\Http\Controllers;

use App\Models\UserProduct;
use App\Models\Product;
use Illuminate\Http\Request;

class UserProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([]);
        }

        return response()->json(
            UserProduct::where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(fn (UserProduct $product) => $this->toView($product))
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'product_id' => ['required', 'string', 'exists:products,id'],
            'total_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'routine_id' => ['nullable', 'string'],
        ]);
        $product = Product::findOrFail($data['product_id']);

        $existing = UserProduct::where('user_email', $user->email)
            ->where('product_id', $data['product_id'])
            ->first();

        if ($existing) {
            return response()->json($this->toView($existing));
        }

        $tracked = UserProduct::create([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_type' => $product->type,
            'product_image' => $product->image,
            'started_at' => now(),
            'current_day' => 1,
            'total_days' => $data['total_days'] ?? 30,
            'routine_id' => $data['routine_id'] ?? null,
        ]);

        return response()->json($this->toView($tracked), 201);
    }

    public function update(Request $request, UserProduct $userProduct)
    {
        abort_unless($userProduct->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'current_day' => ['sometimes', 'integer', 'min:1'],
            'total_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
        ]);

        if (isset($data['current_day'])) {
            abort_if($data['current_day'] > ($data['total_days'] ?? $userProduct->total_days), 422, 'Current day cannot exceed the tracking duration.');
        }

        $userProduct->update($data);

        return response()->json($this->toView($userProduct));
    }

    public function advance(Request $request, UserProduct $userProduct)
    {
        abort_unless($userProduct->user_id === $request->user()->id, 404);

        $userProduct->update([
            'current_day' => min($userProduct->current_day + 1, $userProduct->total_days),
        ]);

        return response()->json($this->toView($userProduct));
    }

    public function destroy(Request $request, UserProduct $userProduct)
    {
        abort_unless($userProduct->user_id === $request->user()->id, 404);

        $userProduct->delete();

        return response()->json(['message' => 'Product removed.']);
    }

    private function toView(UserProduct $product): array
    {
        return [
            'id' => $product->id,
            'userEmail' => $product->user_email,
            'productId' => $product->product_id,
            'productName' => $product->product_name,
            'productType' => $product->product_type,
            'productImage' => $product->product_image,
            'startedAt' => $product->started_at?->valueOf(),
            'currentDay' => $product->current_day,
            'totalDays' => $product->total_days,
            'routineId' => $product->routine_id,
        ];
    }
}