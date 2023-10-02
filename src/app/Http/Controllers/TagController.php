<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends AbstractEntityApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([]);

        $requestParams = $request->all();

        $where = [];

        $rowsPerPage = env('ROWS_PER_PAGE', 1000);

        $page = $request->get('page', 1);

        [$limit, $offset] = $this->calculateLimitOffsetForPage($page, $rowsPerPage);

        $tags = Tag::where($where)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $total = Tag::where($where)->count();

        return $this->respond([
            'tags' => $tags,
            'pagination' => [
                'offset' => $offset,
                'limit' => $limit,
                'recordsFiltered' => count($tags),
                'recordsTotal' => $total
            ]
        ]);
    }
}
