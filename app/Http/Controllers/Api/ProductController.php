<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // ================== LIST ALL PRODUCTS ==================
    public function index()
    {
        // color_names will auto-append from model
        $products = Product::all();
        return response()->json($products, 200);
    }

    // ================== CREATE PRODUCT ==================
    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string',
            'price'        => 'required|integer',
            'color_name'   => 'required|string', // black,yellow
        ]);

        // Convert "black,yellow" → ["black", "yellow"]
        $colorNames = array_map('trim', explode(',', $request->color_name));

        // Get color IDs (case-insensitive)
        $colorIds = Color::whereIn(
            DB::raw('LOWER(color_name)'),
            array_map('strtolower', $colorNames)
        )->pluck('id')->toArray();

        if (count($colorIds) === 0) {
            return response()->json(['message' => 'No valid colors found'], 404);
        }

        // Save product with "1,2"
        $product = Product::create([
            'product_name' => $request->product_name,
            'price'        => $request->price,
            'color_id'     => implode(',', $colorIds),
        ]);

        return response()->json($product, 201);
    }

    // ================== VIEW SINGLE PRODUCT ==================
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product, 200);
    }

    // ================== UPDATE PRODUCT ==================
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $request->validate([
            'product_name' => 'required|string',
            'price'        => 'required|integer',
            'color_name'   => 'required|string',
        ]);

        $colorNames = array_map('trim', explode(',', $request->color_name));

        $colorIds = Color::whereIn(
            DB::raw('LOWER(color_name)'),
            array_map('strtolower', $colorNames)
        )->pluck('id')->toArray();

        if (count($colorIds) === 0) {
            return response()->json(['message' => 'No valid colors found'], 404);
        }

        $product->update([
            'product_name' => $request->product_name,
            'price'        => $request->price,
            'color_id'     => implode(',', $colorIds),
        ]);

        return response()->json($product, 200);
    }

    // ================== DELETE PRODUCT ==================
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], 200);
    }
}
