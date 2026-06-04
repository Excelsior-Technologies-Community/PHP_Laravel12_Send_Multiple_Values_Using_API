<?php
// app/Http/Controllers/Api/ProductController.php (COMPLETE UPDATED VERSION)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Color;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ColorResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // ================== LIST ALL PRODUCTS (WITH PAGINATION) ==================
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $products = Product::with('variants')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Products fetched successfully',
            'data' => ProductResource::collection($products),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ], 200);
    }

    // ================== CREATE PRODUCT (WITH VARIANTS) ==================
    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'color_name' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,webp|max:2048',
            'variants' => 'nullable|array',
            'variants.*.size' => 'required|string',
            'variants.*.quantity' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $data = [
                'product_name' => $request->product_name,
                'price' => $request->price,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/products'), $filename);
                $data['image'] = 'uploads/products/' . $filename;
            }

            // Handle colors (auto-create if not exists)
            $colorNames = array_unique(
                array_map('strtolower', array_map('trim', explode(',', $request->color_name)))
            );

            $colorIds = [];
            foreach ($colorNames as $name) {
                $color = Color::firstOrCreate(['color_name' => $name]);
                $colorIds[] = $color->id;
            }

            $data['color_id'] = implode(',', $colorIds);
            
            // Create product
            $product = Product::create($data);

            // Create variants
            if ($request->has('variants')) {
                foreach ($request->variants as $variant) {
                    $product->variants()->create([
                        'size' => strtoupper($variant['size']),
                        'quantity' => $variant['quantity']
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product created successfully',
                'data' => new ProductResource($product->load('variants'))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================== VIEW SINGLE PRODUCT ==================
    public function show($id)
    {
        $product = Product::with('variants')->find($id);

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
            'product_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'color_name' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,webp|max:2048',
            'variants' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $data = [
                'product_name' => $request->product_name,
                'price' => $request->price,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                if ($product->image && File::exists(public_path($product->image))) {
                    File::delete(public_path($product->image));
                }

                $file = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/products'), $filename);
                $data['image'] = 'uploads/products/' . $filename;
            }

            // Handle colors
            $colorNames = array_unique(
                array_map('strtolower', array_map('trim', explode(',', $request->color_name)))
            );

            $colorIds = [];
            foreach ($colorNames as $name) {
                $color = Color::firstOrCreate(['color_name' => $name]);
                $colorIds[] = $color->id;
            }

            $data['color_id'] = implode(',', $colorIds);
            
            // Update product
            $product->update($data);

            // Update variants (delete old and create new)
            if ($request->has('variants')) {
                $product->variants()->delete();
                foreach ($request->variants as $variant) {
                    $product->variants()->create([
                        'size' => strtoupper($variant['size']),
                        'quantity' => $variant['quantity']
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product updated successfully',
                'data' => new ProductResource($product->load('variants'))
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================== DELETE PRODUCT (SOFT DELETE) ==================
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Delete image if exists
        if ($product->image && File::exists(public_path($product->image))) {
            File::delete(public_path($product->image));
        }

        $product->delete(); // Soft delete

        return response()->json([
            'status' => true,
            'message' => 'Product deleted successfully'
        ], 200);
    }

    // ================== PERMANENT DELETE PRODUCT ==================
    public function forceDelete($id)
    {
        $product = Product::withTrashed()->find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Delete variants first
        $product->variants()->delete();
        
        // Delete image
        if ($product->image && File::exists(public_path($product->image))) {
            File::delete(public_path($product->image));
        }

        $product->forceDelete();

        return response()->json([
            'status' => true,
            'message' => 'Product permanently deleted'
        ], 200);
    }

    // ================== RESTORE SOFT DELETED PRODUCT ==================
    public function restore($id)
    {
        $product = Product::withTrashed()->find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $product->restore();

        return response()->json([
            'status' => true,
            'message' => 'Product restored successfully',
            'data' => new ProductResource($product->load('variants'))
        ], 200);
    }

    // ================== GET ALL SOFT DELETED PRODUCTS ==================
    public function trashed()
    {
        $products = Product::onlyTrashed()->with('variants')->get();

        return response()->json([
            'status' => true,
            'message' => 'Trashed products fetched successfully',
            'data' => ProductResource::collection($products)
        ], 200);
    }

    // ================== SEARCH PRODUCTS BY NAME ==================
    public function search(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|min:2'
        ]);

        $products = Product::where('product_name', 'LIKE', '%' . $request->keyword . '%')
            ->with('variants')
            ->paginate(10);

        return response()->json([
            'status' => true,
            'message' => 'Search results for: ' . $request->keyword,
            'data' => ProductResource::collection($products),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ]
        ], 200);
    }

    // ================== FILTER PRODUCTS BY COLOR ==================
    public function filterByColor(Request $request)
    {
        $request->validate([
            'color_name' => 'required|string'
        ]);

        $colorNames = array_map('trim', explode(',', $request->color_name));
        $colorNames = array_map('strtolower', $colorNames);

        $colorIds = Color::whereIn('color_name', $colorNames)->pluck('id')->toArray();

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

    // ================== FILTER PRODUCTS BY PRICE RANGE ==================
    public function filterByPrice(Request $request)
    {
        $request->validate([
            'min_price' => 'required|numeric|min:0',
            'max_price' => 'required|numeric|min:0|gt:min_price',
        ]);

        $products = Product::whereBetween('price', [$request->min_price, $request->max_price])
            ->with('variants')
            ->get();

        return response()->json([
            'status' => true,
            'message' => "Products between ₹{$request->min_price} and ₹{$request->max_price}",
            'count' => $products->count(),
            'data' => ProductResource::collection($products)
        ], 200);
    }

    // ================== BULK PRODUCT IMPORT (JSON) ==================
    public function bulkImport(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.product_name' => 'required|string',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.color_name' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $imported = [];
            $failed = [];

            foreach ($request->products as $index => $productData) {
                try {
                    // Handle colors
                    $colorNames = array_unique(
                        array_map('strtolower', array_map('trim', explode(',', $productData['color_name'])))
                    );

                    $colorIds = [];
                    foreach ($colorNames as $name) {
                        $color = Color::firstOrCreate(['color_name' => $name]);
                        $colorIds[] = $color->id;
                    }

                    // Create product
                    $product = Product::create([
                        'product_name' => $productData['product_name'],
                        'price' => $productData['price'],
                        'color_id' => implode(',', $colorIds),
                        'image' => $productData['image'] ?? null,
                    ]);

                    // Create variants if provided
                    if (isset($productData['variants']) && is_array($productData['variants'])) {
                        foreach ($productData['variants'] as $variant) {
                            $product->variants()->create([
                                'size' => strtoupper($variant['size']),
                                'quantity' => $variant['quantity'] ?? 0
                            ]);
                        }
                    }

                    $imported[] = $product;

                } catch (\Exception $e) {
                    $failed[] = [
                        'index' => $index,
                        'data' => $productData,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Bulk import completed",
                'imported_count' => count($imported),
                'failed_count' => count($failed),
                'imported_products' => ProductResource::collection(collect($imported)),
                'failed_items' => $failed
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Bulk import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================== EXPORT PRODUCTS (JSON) ==================
    public function export(Request $request)
    {
        $format = $request->get('format', 'json'); // json or csv
        
        $products = Product::with('variants')->get();
        
        $exportData = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'price' => $product->price,
                'color_names' => implode(',', $product->color_names),
                'color_ids' => $product->color_id,
                'image' => $product->image,
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'size' => $variant->size,
                        'quantity' => $variant->quantity
                    ];
                }),
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ];
        });

        if ($format === 'csv') {
            $csvFileName = 'products_export_' . date('Y-m-d_His') . '.csv';
            $callback = function () use ($exportData) {
                $file = fopen('php://output', 'w');
                
                // Add headers
                fputcsv($file, ['ID', 'Product Name', 'Price', 'Colors', 'Color IDs', 'Image', 'Created At', 'Updated At']);
                
                // Add data rows
                foreach ($exportData as $product) {
                    fputcsv($file, [
                        $product['id'],
                        $product['product_name'],
                        $product['price'],
                        $product['color_names'],
                        $product['color_ids'],
                        $product['image'],
                        $product['created_at'],
                        $product['updated_at']
                    ]);
                }
                
                fclose($file);
            };
            
            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
            ]);
        }
        
        // Default JSON export
        return response()->json([
            'status' => true,
            'total_products' => $exportData->count(),
            'export_date' => now()->toDateTimeString(),
            'data' => $exportData
        ], 200);
    }

    // ================== GET COLOR-WISE PRODUCT COUNT ==================
    public function colorWiseCount()
    {
        $colors = Color::all();
        
        $result = $colors->map(function ($color) {
            return [
                'color_id' => $color->id,
                'color_name' => $color->color_name,
                'products_count' => $color->products_count
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Color-wise product count',
            'total_colors' => $colors->count(),
            'data' => $result
        ], 200);
    }

    // ================== GET PRODUCT STATISTICS ==================
    public function statistics()
    {
        $totalProducts = Product::count();
        $totalTrashed = Product::onlyTrashed()->count();
        $totalColors = Color::count();
        $averagePrice = Product::avg('price');
        $minPrice = Product::min('price');
        $maxPrice = Product::max('price');
        
        // Get most expensive product
        $mostExpensive = Product::orderBy('price', 'desc')->first();
        
        // Get cheapest product
        $cheapest = Product::orderBy('price', 'asc')->first();

        return response()->json([
            'status' => true,
            'statistics' => [
                'total_products' => $totalProducts,
                'total_trashed_products' => $totalTrashed,
                'total_colors' => $totalColors,
                'average_price' => round($averagePrice, 2),
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'most_expensive_product' => $mostExpensive ? [
                    'id' => $mostExpensive->id,
                    'name' => $mostExpensive->product_name,
                    'price' => $mostExpensive->price
                ] : null,
                'cheapest_product' => $cheapest ? [
                    'id' => $cheapest->id,
                    'name' => $cheapest->product_name,
                    'price' => $cheapest->price
                ] : null,
            ]
        ], 200);
    }
}