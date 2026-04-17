<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Color;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // ================== LIST ALL PRODUCTS (WITH PAGINATION) ==================
    public function index()
    {
        $products = Product::orderBy('id', 'asc')->paginate(3);

        return response()->json([
            'status' => true,
            'message' => 'Product list fetched successfully',
            'data' => $products
        ], 200);
    }

    // ================== CREATE PRODUCT (AUTO-CREATE COLORS) ==================
    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string',
            'price'        => 'required|integer',
            'color_name'   => 'required|string',
        ]);

        // Clean + remove duplicates + lowercase
        $colorNames = array_unique(
            array_map('strtolower', array_map('trim', explode(',', $request->color_name)))
        );

        $colorIds = [];

        foreach ($colorNames as $name) {
            $color = Color::firstOrCreate([
                'color_name' => $name
            ]);

            $colorIds[] = $color->id;
        }

        $product = Product::create([
            'product_name' => $request->product_name,
            'price'        => $request->price,
            'color_id'     => implode(',', $colorIds),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    // ================== VIEW SINGLE PRODUCT ==================
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
            'data' => $product
        ], 200);
    }

    // ================== UPDATE PRODUCT ==================
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
        ]);

        $colorNames = array_unique(
            array_map('strtolower', array_map('trim', explode(',', $request->color_name)))
        );

        $colorIds = [];

        foreach ($colorNames as $name) {
            $color = Color::firstOrCreate([
                'color_name' => $name
            ]);

            $colorIds[] = $color->id;
        }

        $product->update([
            'product_name' => $request->product_name,
            'price'        => $request->price,
            'color_id'     => implode(',', $colorIds),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ], 200);
    }

    // ================== DELETE PRODUCT ==================
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product deleted successfully'
        ], 200);
    }

    // ================== FILTER BY COLOR ==================
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
            'data' => $products
        ], 200);
    }

    // ================== SEARCH PRODUCT ==================
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
            'data' => $products
        ], 200);
    }
}