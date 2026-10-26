<?php

namespace App\Http\Controllers;

use App\Models\ProductRoutine;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductRoutineController extends Controller
{
    public function index()
    {
        return response()->json(
            ProductRoutine::with('steps')->latest()->get()->map(fn (ProductRoutine $r) => $this->toView($r))
        );
    }

    public function byProduct(string $productId)
    {
        return response()->json(
            ProductRoutine::where('product_id', $productId)
                ->with('steps')
                ->get()
                ->map(fn (ProductRoutine $r) => $this->toView($r))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'string'],
            'product_name' => ['required', 'string'],
            'product_type' => ['required', 'string'],
            'product_image' => ['nullable', 'string'],
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'total_days' => ['required', 'integer', 'min:1'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.day' => ['required', 'integer', 'min:1'],
            'steps.*.title' => ['required', 'string'],
            'steps.*.description' => ['nullable', 'string'],
            'steps.*.instructions' => ['nullable', 'string'],
            'steps.*.tips' => ['nullable', 'string'],
        ]);

        $routine = ProductRoutine::create([
            'routine_id' => 'routine-'.Str::random(8),
            'product_id' => $data['product_id'] ?? null,
            'product_name' => $data['product_name'],
            'product_type' => $data['product_type'],
            'product_image' => $data['product_image'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'total_days' => $data['total_days'],
            'created_by' => $this->createdBy(),
        ]);

        foreach ($data['steps'] as $step) {
            $routine->steps()->create($step);
        }

        $routine->load('steps');

        return response()->json($this->toView($routine), 201);
    }

    public function update(Request $request, ProductRoutine $routine)
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'string'],
            'product_name' => ['sometimes', 'string'],
            'product_type' => ['sometimes', 'string'],
            'product_image' => ['nullable', 'string'],
            'name' => ['sometimes', 'string'],
            'description' => ['nullable', 'string'],
            'total_days' => ['sometimes', 'integer', 'min:1'],
            'steps' => ['sometimes', 'array', 'min:1'],
            'steps.*.day' => ['required_with:steps', 'integer', 'min:1'],
            'steps.*.title' => ['required_with:steps', 'string'],
            'steps.*.description' => ['nullable', 'string'],
            'steps.*.instructions' => ['nullable', 'string'],
            'steps.*.tips' => ['nullable', 'string'],
        ]);

        $routine->update(collect($data)->only([
            'product_id', 'product_name', 'product_type', 'product_image', 'name', 'description', 'total_days',
        ])->all());

        if (isset($data['steps'])) {
            $routine->steps()->delete();
            foreach ($data['steps'] as $step) {
                $routine->steps()->create($step);
            }
        }

        $routine->load('steps');

        return response()->json($this->toView($routine));
    }

    public function destroy(ProductRoutine $routine)
    {
        $routine->delete();

        return response()->json(['message' => 'Routine deleted.']);
    }

    private function createdBy(): string
    {
        $user = request()->user();

        if (! $user) {
            return 'User';
        }

        return $user instanceof Admin ? 'Admin' : 'User';
    }

    private function toView(ProductRoutine $r): array
    {
        return [
            'id' => $r->routine_id,
            'productId' => $r->product_id,
            'productName' => $r->product_name,
            'productType' => $r->product_type,
            'productImage' => $r->product_image,
            'name' => $r->name,
            'description' => $r->description,
            'totalDays' => $r->total_days,
            'steps' => $r->steps->map(fn ($s) => [
                'day' => $s->day,
                'title' => $s->title,
                'description' => $s->description,
                'instructions' => $s->instructions,
                'tips' => $s->tips,
            ])->values(),
            'createdBy' => $r->created_by,
            'createdAt' => $r->created_at?->valueOf(),
            'updatedAt' => $r->updated_at?->valueOf(),
        ];
    }
}