<?php

namespace NextDeveloper\Options\Services;

use Illuminate\Support\Str;
use NextDeveloper\Options\Database\Models\Requests;
use NextDeveloper\Options\Services\DeprecationService;

class PostmanService
{
    public static function generate(): array
    {
        $routes = Requests::all();

        $grouped = $routes->groupBy('topic');

        $folders = [];

        foreach ($grouped as $topic => $items) {
            $folderItems = [];

            foreach ($items as $route) {
                $isDeprecated = DeprecationService::isDeprecated($route->uri, $route->method);
                $normalizedUri = str_replace(':object-id', '{{object_id}}', $route->uri);
                $middleware = $route->middleware ?? [];

                $name = $route->method . ' ' . $route->uri;
                if ($isDeprecated) {
                    $name = '[DEPRECATED] ' . $name;
                }

                $headers = [
                    ['key' => 'Accept', 'value' => 'application/json'],
                    ['key' => 'Content-Type', 'value' => 'application/json'],
                ];

                if (in_array('auth:api', $middleware) || in_array('auth:sanctum', $middleware)) {
                    $headers[] = ['key' => 'Authorization', 'value' => 'Bearer {{token}}', 'type' => 'text'];
                }

                $url = [
                    'raw'  => '{{base_url}}/' . $normalizedUri,
                    'host' => ['{{base_url}}'],
                    'path' => explode('/', $normalizedUri),
                ];

                if ($route->method === 'GET' && $route->search_filters) {
                    $query = [];
                    foreach ($route->search_filters as $f) {
                        $query[] = [
                            'key'         => $f['name'],
                            'value'       => '',
                            'disabled'    => true,
                            'description' => $f['description'] ?? '',
                        ];
                    }
                    $url['query'] = $query;
                }

                $request = [
                    'method'      => $route->method,
                    'description' => $route->action_description,
                    'header'      => $headers,
                    'url'         => $url,
                ];

                if (in_array($route->method, ['POST', 'PATCH', 'PUT']) && $route->requests) {
                    $fields = $route->requests[0] ?? [];
                    $example = array_fill_keys(array_keys($fields), '');
                    $request['body'] = [
                        'mode'    => 'raw',
                        'raw'     => json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                        'options' => ['raw' => ['language' => 'json']],
                    ];
                }

                $item = [
                    'name'       => $name,
                    'request'    => $request,
                    'deprecated' => $isDeprecated,
                ];

                $folderItems[] = $item;
            }

            $folders[] = [
                'name' => $topic ?: 'General',
                'item' => $folderItems,
            ];
        }

        return [
            'info' => [
                'name'          => 'PlusClouds API',
                '_postman_id'   => Str::uuid()->toString(),
                'description'   => 'Auto-generated from route metadata',
                'schema'        => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item'     => $folders,
            'variable' => [
                ['key' => 'base_url', 'value' => config('app.url'), 'type' => 'string'],
                ['key' => 'token',    'value' => '',                'type' => 'string'],
                ['key' => 'object_id','value' => '',                'type' => 'string'],
            ],
        ];
    }
}
