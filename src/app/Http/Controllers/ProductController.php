<?php

namespace App\Http\Controllers;

use App\Events\ProductSavedEvent;
use App\Models\Product;
use App\Models\ProductTag;
use App\Models\Tag;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends AbstractEntityApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'integer',
            'category_id' => 'integer',
        ]);

        $requestParams = $request->all();

        $where = [];

        if (array_key_exists('category_id', $requestParams)) {
            $where['category_id'] = $requestParams['category_id'];
        }

        $rowsPerPage = env('ROWS_PER_PAGE', 1000);

        $page = $request->get('page', 1);

        [$limit, $offset] = $this->calculateLimitOffsetForPage($page, $rowsPerPage);

        $products = Product::where($where)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $productsArray = $this->prepareDataFromCollection($products);

        $total = Product::where($where)->count();

        return $this->respond([
            'products' => $productsArray,
            'pagination' => [
                'offset' => $offset,
                'limit' => $limit,
                'records_filtered' => count($products),
                'records_total' => $total
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:10|regex:/^[a-zA-Z-_\s]+$/',
            'category_id' => 'required|integer',
            'price' => 'required|numeric',
            'release_date' => 'required|date',
            'tags' => 'array|exists:tags,id'
        ]);

        $requestParams = $request->all();

        $createParams = [
            'name' => $requestParams['name'],
            'code' => $this->generateUniqueCode($requestParams['name']),
            'category_id' => $requestParams['category_id'],
            'price' => $requestParams['price'],
            'release_date' => $requestParams['release_date'],
        ];

        DB::beginTransaction();

        try {
            $product = Product::create($createParams);
            if (array_key_exists('tags', $requestParams)) {
                $product->tags()->sync($requestParams['tags']);
            }

            DB::commit();

            ProductSavedEvent::dispatch($product);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondInternalError();
        }

        $productsArray = $this->prepareDataFromCollection(collect([$product]));

        return $this->respond(['product' => $productsArray[0]]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $productsArray = $this->prepareDataFromCollection(collect([$product]));

        return $this->respond(['product' => $productsArray[0]]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $requestParams = $request->all();

        if (empty($requestParams)) {
            return $this->respond(['product' => $product]);
        }

        $request->validate([
            'name' => 'string|max:10|regex:/^[a-zA-Z-_\s]+$/',
            'category_id' => 'integer',
            'price' => 'numeric',
            'release_date' => 'date',
            'tags' => 'array|exists:tags,id'
        ]);

        $fillables = $product->getFillable();

        if (($key = array_search('code', $fillables )) !== false) {
            unset($fillables[$key]);
        }

        $updateParams = [];

        foreach ($product->getFillable() as $key) {
            if (array_key_exists($key, $requestParams)) {
                $updateParams[$key] = $requestParams[$key];
            }
        }

        // When updating the name
        if (array_key_exists('name', $requestParams) && $requestParams['name'] !== $product->name) {
            $updateParams['code'] = $this->generateUniqueCode($updateParams['name']);
        }

        DB::beginTransaction();

        try {
            $product->update($updateParams);
            if (array_key_exists('tags', $requestParams)) {
                $product->tags()->sync($requestParams['tags']);
            }

            DB::commit();

            ProductSavedEvent::dispatch($product);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->respondInternalError();
        }

        $productsArray = $this->prepareDataFromCollection(collect([$product]));

        return $this->respond(['product' => $productsArray[0]]);
    }

    /**
     * Generate unique code based on the name.
     */
    private function generateUniqueCode(string $name)
    {
        // Limitation: up to 25 products with same name
        $alphabet = range('a', 'z');

        $code = Str::slug($name);
        $existingCodes = Product::where('code', 'like', $code . '%')->pluck('code');

        if ($existingCodes->count() === 0) {
            return $code;
        }

        return $code . '-' . $alphabet[$existingCodes->count()];
    }

    /**
     * Prepare data from collection.
     */
    public function prepareDataFromCollection(EloquentCollection|Collection $products): array
    {
        if ($products->count() === 0) {
            return [];
        }

        // The following is an effort to avoid the N+1 problem
        // Get all the product ids involved in the paginated products
        $productIds = $products->pluck('id');

        // Get the rows of product_tag table for all the product ids
        $productTags = ProductTag::whereIn('product_id', $productIds)->get();

        // Get all the tag rows in order to get the name we are missing
        $tagIds = $productTags->pluck('tag_id');
        $tags = Tag::whereIn('id', $tagIds)->get();

        // Create an array that works as an index with all the tag names
        $tagIndex = [];
        foreach ($tags as $tag) {
            $tagIndex[$tag->id] = $tag;
        }

        $productTagConnections = [];
        foreach ($productTags as $productTag) {
            if (!array_key_exists($productTag->product_id, $productTagConnections)) {
                $productTagConnections[$productTag->product_id] = [];
            }

            $tag = $tagIndex[$productTag->tag_id];

            $productTagConnections[$productTag->product_id][] = [
                'id' => $productTag->tag_id,
                'name' => $tag->name,
                'created_at' => $tag->created_at,
                'updated_at' => $tag->updated_at,
            ];
        }

        $productsArray = $products->toArray();

        foreach ($productsArray as $index => $productArray) {
            $productsArray[$index]['tags'] = [];
            if (array_key_exists($productArray['id'], $productTagConnections)) {
                $productsArray[$index]['tags'] = $productTagConnections[$productArray['id']];
            }
        }

        return $productsArray;
    }
}
