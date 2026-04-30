<?php

namespace NextDeveloper\Options\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NextDeveloper\Options\Database\Models\Requests;
use NextDeveloper\Options\Services\OptionsService;

class OpenApiController
{
    public function schema(Request $request): JsonResponse
    {
        return response()->json(json_decode(OptionsService::createJSON($request->query('module')), true));
    }

    public function modules(): JsonResponse
    {
        $modules = Requests::select('topic')
            ->distinct()
            ->whereNotNull('topic')
            ->where('topic', '!=', '')
            ->orderBy('topic')
            ->pluck('topic');

        return response()->json($modules);
    }
}
