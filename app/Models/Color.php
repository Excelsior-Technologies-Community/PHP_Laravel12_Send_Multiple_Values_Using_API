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
