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

