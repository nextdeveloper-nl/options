<?php

namespace NextDeveloper\Options\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NextDeveloper\Options\Services\PostmanService;

class PostmanController
{
    public function collection(Request $request): JsonResponse
    {
        return response()->json(PostmanService::generate($request->query('module')));
    }
}
