<?php

namespace NextDeveloper\Options\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NextDeveloper\Options\Services\ChangelogService;

class ChangelogController
{
    public function index(): JsonResponse
    {
        return response()->json(ChangelogService::all());
    }

    public function forRoute(Request $request): JsonResponse
    {
        $request->validate(['uri' => 'required|string', 'method' => 'required|string']);
        return response()->json(ChangelogService::getForRoute($request->uri, strtoupper($request->method)));
    }
}
