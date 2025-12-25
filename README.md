# PHP_Laravel12_Send_Multiple_Values_Using_API

---

## Project Explanation

### Conclusion

---
This project demonstrates how to handle multiple related values in Laravel 12 APIs
using a master–child structure, request validation, and Eloquent ORM.


### Project Overview

---

PHP_Laravel12_Send_Multiple_Values_Using_API is a Laravel 12–based REST API project designed to handle multiple values passed through an API request and store them correctly in a relational database using a master–child structure.

### The project focuses on:
---

API-only development

Clean separation of concerns

Handling comma-separated input values

Converting readable input into database-ready data


### Project Objective

---
The main objective of this project is to:

Manage colors as a master entity

Create and manage products using API endpoints

Accept multiple color names in a single API request

Convert those color names into corresponding color IDs

Store the IDs in the products table in a structured format

This approach demonstrates how to process and persist multiple related values using Laravel APIs.



### Architecture Summary
---
Framework: Laravel 12

Type: REST API (No Blade / No UI)

Database: MySQL

Pattern Used: Master–Child

Communication Format: JSON

Tools Used: Artisan CLI, Eloquent ORM, Postman

## Project Set-Up

---

## STEP 1: Create New Laravel 12 Project

### Open CMD / Terminal and run:

```
composer create-project laravel/laravel:^12.0 PHP_Laravel12_Send_Multiple_Values_Using_API
```

### Go inside project:

```
cd PHP_Laravel12_Send_Multiple_Values_Using_API
```

### Run project:
```
php artisan serve
```

### Open browser:
```
http://127.0.0.1:8000
```


## STEP 2: Database Configuration

### Open .env file and update:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_multiple_values_api
DB_USERNAME=root
DB_PASSWORD=


```


### Now create database manually in phpMyAdmin:
```
Database Name: laravel_multiple_values_api
```



## STEP 3: Create Model + Migration

We need a table to store multiple values.

### Run command:

```
php artisan make:model Product -m
php artisan make:model Color -m

```

This creates:
```
app/Models/Product.php
database/migrations/xxxx_create_products_table.php
app/Models/Color.php
database/migrations/xxxx_create_colors_table.php

```



## STEP 4: Write Migration Code

### Open migration file:

database/migrations/xxxx_create_products_table.php

```

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->integer('price');
             $table->string('color_id')->nullable(); // or create it as string if new table
            $table->timestamps();

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

```

database/migrations/xxxx_create_colors_table.php

```

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('colors', function (Blueprint $table) {
    $table->id();
    $table->string('color_name')->unique(); // match controller
    $table->timestamps();
});

}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};


```


### Run migration:
```
php artisan migrate
```

Table created successfully


## STEP 5: Model Setup


Open:

app/Models/Product.php

```

<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['product_name', 'price', 'color_id'];

    

    public function getColorNamesAttribute()
    {
        if (!$this->color_id) return [];

        $ids = explode(',', $this->color_id);
        return \App\Models\Color::whereIn('id', $ids)->pluck('color_name');
    }
}

```

app/Models/Color.php

```

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasFactory;

   protected $fillable = ['color_name'];

    // One Color has many Products
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}


```

## STEP 6: Create API Controller

### Run command:

```

php artisan make:controller Api/ProductController
php artisan make:controller Api/ColorController 

```

File created:

 app/Http/Controllers/Api/ProductController.php
 app/Http/Controllers/Api/ColorController.php



## STEP 7: Write Controller Code (MAIN PART)


app/Http/Controllers/Api/ProductController.php

```

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


```
 app/Http/Controllers/Api/ColorController.php

```

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    // GET: All Colors
    public function index()
    {
        return response()->json(Color::all(), 200);
    }

    // POST: Create Color
    public function store(Request $request)
    {
        $request->validate([
            'color_name' => 'required|unique:colors,color_name',
        ]);

        $color = Color::create([
            'color_name' => $request->color_name
        ]);

        return response()->json($color, 201);
    }

    // GET: Single Color
    public function show($id)
    {
        $color = Color::find($id);
        if (!$color) {
            return response()->json(['message' => 'Color not found'], 404);
        }
        return response()->json($color, 200);
    }

    // PUT/PATCH: Update Color
    public function update(Request $request, $id)
    {
        $color = Color::find($id);
        if (!$color) {
            return response()->json(['message' => 'Color not found'], 404);
        }

        $request->validate([
            'color_name' => 'required|unique:colors,color_name,' . $id,
        ]);

        $color->update([
            'color_name' => $request->color_name
        ]);

        return response()->json($color, 200);
    }

    // DELETE: Remove Color
    public function destroy($id)
    {
        $color = Color::find($id);
        if (!$color) {
            return response()->json(['message' => 'Color not found'], 404);
        }

        $color->delete();
        return response()->json(['message' => 'Color deleted'], 200);
    }
}


```

## STEP 8: Create API Routes

### Open:

 routes/api.php:

```

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ColorController;
use App\Http\Controllers\Api\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes (Readable Style)
|--------------------------------------------------------------------------
*/

// -------------------- COLORS CRUD --------------------
// Create a new color
Route::post('/color/create', [ColorController::class, 'store']);

// List all colors
Route::get('/color/list', [ColorController::class, 'index']);

// View a single color
Route::get('/color/view/{id}', [ColorController::class, 'show']);

// Update a color
Route::post('/color/update/{id}', [ColorController::class, 'update']); // POST used for update

// Delete a color
Route::post('/color/delete/{id}', [ColorController::class, 'destroy']);


// -------------------- PRODUCTS CRUD --------------------
// Create a new product
Route::post('/product/create', [ProductController::class, 'store']);

// List all products
Route::get('/product/list', [ProductController::class, 'index']);

// View a single product
Route::get('/product/view/{id}', [ProductController::class, 'show']);

// Update a product
Route::post('/product/update/{id}', [ProductController::class, 'update']); // POST used for update

// Delete a product
Route::post('/product/delete/{id}', [ProductController::class, 'destroy']);


```



## STEP 9: Test API in Postman (IMPORTANT)


### Color Crud:



1. ### CREATE Color

 URL
```
POST http://127.0.0.1:8000/api/color/create
```

 Headers
```
Content-Type: application/json
Accept: application/json
```

 Body → raw → JSON

```
{
  "color_name": "green"
}

```

Response:


<img width="1446" height="910" alt="Screenshot 2025-12-24 132542" src="https://github.com/user-attachments/assets/9ebdf1f6-98ac-4a6d-ad19-87fd6ea194b5" />



2. ### List Color

 URL
```
GET http://127.0.0.1:8000/api/color/list
```


Response:

<img width="1442" height="918" alt="Screenshot 2025-12-24 132622" src="https://github.com/user-attachments/assets/5e353d72-e9c5-40c7-a88f-43c8af852f19" />



3. ### Update Color

 URL
```
POST http://127.0.0.1:8000/api/color/update/1
```

 Body → raw → JSON

```
{
  "color_name": "black"
}


```

Response:


<img width="1442" height="904" alt="Screenshot 2025-12-24 132706" src="https://github.com/user-attachments/assets/c92aa4ec-08e8-430d-add7-34e2a21c2cb1" />



4. ### View Color

 URL
```
GET http://127.0.0.1:8000/api/color/view/3
```


Response:

<img width="1442" height="915" alt="Screenshot 2025-12-24 132742" src="https://github.com/user-attachments/assets/508a8dff-69a9-4fc6-8e02-78b675dd0f36" />



5. ### Delete Color

 URL
```
POST http://127.0.0.1:8000/api/color/delete/4
```

Response:

<img width="1442" height="912" alt="Screenshot 2025-12-24 142633" src="https://github.com/user-attachments/assets/5055c69f-65f2-40a1-ac9e-344372691dff" />



### Product Crud:



1. ### List Product

 URL
```
GET http://127.0.0.1:8000/api/product/list
```


Response:


<img width="1443" height="910" alt="image" src="https://github.com/user-attachments/assets/3a326ae0-6744-4315-81c1-69d6d7433fa4" />




 2. ### CREATE Product

 URL
```
POST http://127.0.0.1:8000/api/product/create
```

 Headers
```
Content-Type: application/json
Accept: application/json
```

 Body → raw → JSON

```
{
  "product_name": "bag",
  "price": 400,
  "color_name": "red,blue"
}


```

Response:




<img width="1454" height="912" alt="Screenshot 2025-12-24 142252" src="https://github.com/user-attachments/assets/4b6aba3f-b9ed-43a6-80d1-0058d2da9b2e" />



3. ### Update Product

 URL
```
POST http://127.0.0.1:8000/api/product/update/2
```

 Body → raw → JSON

```
{
  "product_name": "t-shirt",
  "price": 600,
  "color_name": "red,blue"
}


```

Response:


<img width="1446" height="956" alt="Screenshot 2025-12-24 142358" src="https://github.com/user-attachments/assets/c6d4b70f-4fb0-452d-9a10-8c17c8719e3a" />



4. ### View Color

 URL
```
GET http://127.0.0.1:8000/api/product/view/1
```


Response:

<img width="1439" height="913" alt="image" src="https://github.com/user-attachments/assets/174ead87-58fb-47c7-b6a4-a621c2967beb" />



5. ### Delete Color

 URL
```
POST http://127.0.0.1:8000/api/product/delete/3
```

Response:

<img width="1443" height="912" alt="Screenshot 2025-12-24 142618" src="https://github.com/user-attachments/assets/68808373-1a08-4d0f-af0f-3cf97e459e6b" />



# Project Folder Structure:


```

PHP_Laravel12_Send_Multiple_Values_Using_API
│
├── app
│   ├── Models
│   │   ├── Product.php      # Product model logic
│   │   └── Color.php        # Color model logic
│   │
│   └── Http
│       └── Controllers
│           └── Api
│               ├── ProductController.php
│               └── ColorController.php
│
├── database
│   └── migrations
│       ├── xxxx_create_products_table.php
│       └── xxxx_create_colors_table.php
│
├── routes
│   └── api.php              # All API routes
│
├── .env                     # Database configuration
├── README.md                # Project documentation
└── composer.json
```
