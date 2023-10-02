<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

abstract class AbstractEntityApiController extends Controller
{
    public function respondInternalError(string $message = ''): JsonResponse
    {
        return $this->sendRespond(
            [
                'status' => 'error',
                'message' => (!empty($message)) ? $message : 'Something went wrong!'
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function respond(array $data): JsonResponse
    {
        return $this->sendRespond(
            [
                'status' => 'success',
                'data' => $data
            ],
            Response::HTTP_OK
        );
    }
    
    public function sendRespond(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    public function calculateLimitOffsetForPage(int $page = 1, int $rowsPerPage = 1000): array
    {
        $limit = $rowsPerPage;

        $offset = ($page - 1) * $limit;

        return [
            $limit,
            $offset
        ];
    }
}
