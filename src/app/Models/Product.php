<?php

namespace App\Models;

use App\Events\ProductSavedEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'category_id', 'price', 'release_date'];

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }
}
