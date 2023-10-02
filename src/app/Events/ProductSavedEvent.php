<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
 
class ProductSavedEvent
{
    use Dispatchable, SerializesModels;
 
    public $product;
 
    /**
     * Create a new event instance.
     *
     * @param Product $user
     */
    public function __construct(Product $product)
    {
        $product->loadMissing('tags');

        $productArray = $product->toArray();

        foreach ($productArray['tags'] as $index => $tag) {
            unset($productArray['tags'][$index]['pivot']);
        }

        $this->product = $productArray;
    }
}
