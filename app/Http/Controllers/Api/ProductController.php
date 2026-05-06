<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Color;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('id', 'asc')->paginate(3);

        return response()->json([
            'status' => true,
            'message' => 'Product list fetched successfully',
            'data' => ProductResource::collection($products)->response()->getData(true)
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string',
            'price'        => 'required|integer',
            'color_name'   => 'required|string',
            'image'        => 'nullable|image|mimes:jpg,png,jpeg,webp|max:2048',
        ]);

        $data = [
            'product_name' => $request->product_name,
            'price'        => $request->price,
        ];

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $data['image'] = 'uploads/products/' . $filename;
        }

        $colorNames = array_unique(
            array_map('strtolower', array_map('trim', explode(',', $request->color_name)))
        );

        $colorIds = [];
        foreach ($colorNames as $name) {
            $color = Color::firstOrCreate(['color_name' => $name]);
            $colorIds[] = $color->id;
        }

        $data['color_id'] = implode(',', $colorIds);
        $product = Product::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Product created successfully',
            'data' => new ProductResource($product)
        ], 201);
    }

    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => new ProductResource($product)
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $request->validate([
            'product_name' => 'required|string',
            'price'        => 'required|integer',
            'color_name'   => 'required|string',
            'image'        => 'nullable|image|mimes:jpg,png,jpeg,webp|max:2048',
        ]);

        $data = [
            'product_name' => $request->product_name,
            'price'        => $request->price,
        ];

        if ($request->hasFile('image')) {
            if ($product->image && File::exists(public_path($product->image))) {
                File::delete(public_path($product->image));
            }

            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $data['image'] = 'uploads/products/' . $filename;
        }

        $colorNames = array_unique(
            array_map('strtolower', array_map('trim', explode(',', $request->color_name)))
        );

        $colorIds = [];
        foreach ($colorNames as $name) {
            $color = Color::firstOrCreate(['color_name' => $name]);
            $colorIds[] = $color->id;
        }

        $data['color_id'] = implode(',', $colorIds);
        $product->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product)
        ], 200);
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        if ($product->image && File::exists(public_path($product->image))) {
            File::delete(public_path($product->image));
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product deleted successfully'
        ], 200);
    }

    public function filterByColor(Request $request)
    {
        $request->validate([
            'color_name' => 'required|string'
        ]);

        $colorNames = array_map('trim', explode(',', $request->color_name));

        $colorIds = Color::whereIn('color_name', array_map('strtolower', $colorNames))
            ->pluck('id')
            ->toArray();

        if (count($colorIds) === 0) {
            return response()->json([
                'status' => false,
                'message' => 'No matching colors found'
            ], 404);
        }

        $products = Product::all()->filter(function ($product) use ($colorIds) {
            $productColorIds = explode(',', $product->color_id);
            return count(array_intersect($productColorIds, $colorIds)) > 0;
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Filtered products fetched successfully',
            'data' => ProductResource::collection($products)
        ], 200);
    }

    public function search(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string'
        ]);

        $products = Product::where('product_name', 'LIKE', '%' . $request->keyword . '%')
            ->orderBy('id', 'asc')
            ->paginate(3);

        return response()->json([
            'status' => true,
            'message' => 'Search results',
            'data' => ProductResource::collection($products)->response()->getData(true)
        ], 200);
    }
}