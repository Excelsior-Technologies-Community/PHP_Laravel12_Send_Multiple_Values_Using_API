<?php
// app/Models/Product.php (Updated)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_name', 
        'price', 
        'image', 
        'color_id'
    ];

    protected $appends = ['color_names']; // Auto append accessor

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function getColorNamesAttribute()
    {
        if (!$this->color_id) return [];

        $ids = explode(',', $this->color_id);
        return \App\Models\Color::whereIn('id', $ids)->pluck('color_name');
    }

    // Get color IDs as array
    public function getColorIdsAttribute()
    {
        if (!$this->color_id) return [];
        return explode(',', $this->color_id);
    }

    // Relationship with variants
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}