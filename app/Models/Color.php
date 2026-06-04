<?php
// app/Models/Color.php (Updated)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasFactory;

    protected $fillable = ['color_name'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Get total products count for this color
    public function getProductsCountAttribute()
    {
        $products = Product::all();
        $count = 0;
        foreach ($products as $product) {
            $colorIds = explode(',', $product->color_id);
            if (in_array($this->id, $colorIds)) {
                $count++;
            }
        }
        return $count;
    }
}