<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(
            Product::latest()->get()->map(fn (Product $product) => $this->toView($product))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'required_without:image_file', 'string'],
            'image_file' => ['nullable', 'required_without:image', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'alt' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'featured' => ['sometimes', 'boolean'],
        ]);

        if ($request->hasFile('image_file')) {
            $data['image'] = $this->storeImage($request);
        }
        unset($data['image_file']);

        $product = Product::create($data);

        return response()->json($this->toView($product), 201);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'image' => ['sometimes', 'nullable', 'string'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'alt' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'featured' => ['sometimes', 'boolean'],
        ]);

        $oldImage = $product->image;
        if ($request->hasFile('image_file')) {
            $data['image'] = $this->storeImage($request);
        } elseif (empty($data['image'])) {
            unset($data['image']);
        }
        unset($data['image_file']);

        $product->update($data);
        if (isset($data['image']) && $data['image'] !== $oldImage) {
            $this->deleteStoredImage($oldImage);
        }

        return response()->json($this->toView($product));
    }

    public function destroy(Product $product)
    {
        $image = $product->image;
        $product->delete();
        $this->deleteStoredImage($image);

        return response()->json(['message' => 'Product deleted.']);
    }

    private function storeImage(Request $request): string
    {
        $path = $request->file('image_file')->store('products', 'public');
        if (! $path) {
            throw new RuntimeException('Unable to store uploaded product image.');
        }

        return $path;
    }

    private function deleteStoredImage(?string $image): void
    {
        if ($image && str_starts_with($image, 'products/')) {
            Storage::disk('public')->delete($image);
        }
    }

    private function toView(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'type' => $product->type,
            'price' => (float) $product->price,
            'image' => $product->image && str_starts_with($product->image, 'products/')
                ? Storage::disk('public')->url($product->image)
                : $product->image,
            'alt' => $product->alt,
            'description' => $product->description,
            'featured' => $product->featured,
        ];
    }
}
