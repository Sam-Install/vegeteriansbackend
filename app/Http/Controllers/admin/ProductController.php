<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $validator = $this->validateProduct($request);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::create([
            'name'        => $request->name,
            'category'    => $request->category,
            'unit'        => $request->unit,
            'old_price'   => $request->old_price,
            'new_price'   => $request->new_price,
            'stock'       => $request->stock,
            'description' => $request->description,
            'status'      => $request->status ?? 'active',
            'images'      => $this->storeImages($request),
        ]);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        $validator = $this->validateProduct($request, $product->id);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Existing images the admin chose to keep (sent as a JSON string from the form)
        $keep = $request->input('existing_images', []);
        if (is_string($keep)) {
            $keep = json_decode($keep, true) ?: [];
        }

        // Delete any removed images from disk
        foreach (($product->images ?? []) as $path) {
            if (!in_array($path, $keep, true)) {
                Storage::disk('public')->delete($path);
            }
        }

        $newImages = $this->storeImages($request);
        $images = array_slice(array_merge($keep, $newImages), 0, 5);

        $product->update([
            'name'        => $request->name,
            'category'    => $request->category,
            'unit'        => $request->unit,
            'old_price'   => $request->old_price,
            'new_price'   => $request->new_price,
            'stock'       => $request->stock,
            'description' => $request->description,
            'status'      => $request->status ?? $product->status,
            'images'      => $images,
        ]);

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        foreach (($product->images ?? []) as $path) {
            Storage::disk('public')->delete($path);
        }
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }

    // Public: storefront reads active products only (no auth required)
    public function publicIndex(Request $request)
    {
        $query = Product::where('status', 'active');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->latest()->get());
    }

    public function publicShow(Product $product)
    {
        if ($product->status !== 'active') {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json($product);
    }

    private function validateProduct(Request $request, $productId = null)
    {
        $keep = $request->input('existing_images', []);
        if (is_string($keep)) {
            $keep = json_decode($keep, true) ?: [];
        }
        $keepCount = is_array($keep) ? count($keep) : 0;
        $newCount = is_array($request->file('images')) ? count($request->file('images')) : 0;

        return Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'category'    => 'required|in:fruits,vegetables,meats,pantry',
            'unit'        => 'required|string|max:50',
            'old_price'   => 'nullable|numeric|min:0',
            'new_price'   => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:active,draft',
            'images'      => [function ($attribute, $value, $fail) use ($keepCount, $newCount) {
                if ($keepCount + $newCount > 5) {
                    $fail('You can only have up to 5 images per product.');
                }
            }],
            'images.*'    => 'image|max:2048',
        ]);
    }

    private function storeImages(Request $request): array
    {
        $paths = [];
        foreach ($request->file('images', []) as $image) {
            $paths[] = $image->store('products', 'public');
        }
        return $paths;
    }
}