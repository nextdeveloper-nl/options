<?php

namespace NextDeveloper\Options\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NextDeveloper\Options\Services\OptionsService;

class OpenApiController
{
    public function schema(): JsonResponse
    {
        return response()->json(json_decode(OptionsService::createJSON(), true));
    }
}
