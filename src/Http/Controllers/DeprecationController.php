<?php

namespace NextDeveloper\Options\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NextDeveloper\Options\Services\DeprecationService;

class DeprecationController
{
    public function index(): JsonResponse
    {
        return response()->json(DeprecationService::all());
    }

    public function deprecate(Request $request): JsonResponse
    {
        $request->validate([
            'uri'         => 'required|string',
            'method'      => 'required|string',
            'note'        => 'required|string',
            'sunset_date' => 'nullable|date',
        ]);
        DeprecationService::deprecate($request->uri, strtoupper($request->method), $request->note, $request->sunset_date);
        return response()->json(['message' => 'Route marked as deprecated']);
    }

    public function undeprecate(Request $request): JsonResponse
    {
        $request->validate(['uri' => 'required|string', 'method' => 'required|string']);
        DeprecationService::undeprecate($request->uri, strtoupper($request->method));
        return response()->json(['message' => 'Deprecation removed']);
    }
}
