<?php

namespace NextDeveloper\Options\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NextDeveloper\Options\Services\PostmanService;

class PostmanController
{
    public function collection(): JsonResponse
    {
        return response()->json(PostmanService::generate());
    }
}
