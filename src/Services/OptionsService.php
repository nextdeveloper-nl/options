<?php

namespace NextDeveloper\Options\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use NextDeveloper\Commons\Common\Timer\Timer;
use NextDeveloper\Options\Database\Models\Requests;
use NextDeveloper\Options\Services\ChangelogService;
use NextDeveloper\Options\Services\DeprecationService;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class OptionsService
{
    public static function getOptions($route) {
        if(Str::startsWith($route, '/')) {
            //  We are removing the slash in the begining
            $route = substr($route, 1);
        }

        if(Str::contains($route, '?')) {
            $route = substr($route, 0, strpos($route, '?'));
        }

        $explodedRoute = explode('/', $route);

        $implodedRoute = '';
        for($i = 0; $i < count($explodedRoute); $i++) {
            if(Str::isUuid($explodedRoute[$i]))
                $implodedRoute .= ':object-id/';
            else
                $implodedRoute .= $explodedRoute[$i] . '/';
        }

        $route = substr($implodedRoute, 0, strlen($implodedRoute) - 1);

        $options = Requests::where('uri', $route)->first();

        $data['uri'] =   $options ? $options['uri'] : 'No direct request can be sent to this URI';
        $data['description']    =   $options ? $options['controller_description']: 'There is no direct request to this URI thus no description.';
        $data['directories']    =   self::getDirectories($route);

        if(!$options)
            return [];

        $data['availableOperations'] = self::getAvailableOperations($options);

        $methods = Requests::where('uri', $route)->get();

        $data['methods'] = [];

        foreach ($methods as $method) {
            switch ($method['method']) {
                case 'GET':
                    $data['methods']['GET']     =   self::buildGetRequest($method);
                    break;
                case 'POST':
                    $data['methods']['POST']    =   self::buildPostRequest($method);
                    break;
                case 'PUT':
                    $data['methods']['PUT']     =   [
                        'fields'    =>  $method['requests']
                    ];
                    break;
                case 'PATCH':
                    $data['methods']['PATCH']     =   [
                        'fields'    =>  json_decode($method['requests'], true)
                    ];
                    break;
                case 'DELETE':
                    $data['methods']['DELETE'] =   [
                        'returns'   =>  null
                    ];
                    break;
            }
        }

        $deprecation = DeprecationService::get($route, $options->method ?? 'GET');
        $data['deprecated'] = $deprecation ?: false;

        foreach ($data['methods'] as $httpMethod => &$methodData) {
            $methodData['curl'] = self::buildCurlSnippet($httpMethod, $route, $options);
        }
        unset($methodData);

        return $data;
    }

    public static function getAvailableOperations(Requests $requests) {
        $explodedRequest = explode('\\', $requests->controller);
        $explodedUri = explode('/', $requests->uri);

        $model = Str::ucfirst(Str::camel($explodedUri[1]));

        $service = $explodedRequest[0] . '\\' . $explodedRequest[1] . '\\Services\\' . $model . 'Service';

        try {
            $modelService = new ReflectionClass($service);
        } catch (ReflectionException $exception) {
            return [];
        }

        $availableOperations = $modelService->getStaticProperties();

        $arrayKeys = array_keys($availableOperations);

        if(array_key_exists('availableOperations', $availableOperations))
            $ops = $availableOperations['availableOperations'];
        else
            $ops = [];

        $operations = [];

        foreach ($ops as $op) {
            $method = new ReflectionMethod($service, $op);

            $operations[$op] = [
                'name'  =>  $op,
                'description'   =>  self::stripComment($method->getDocComment())
            ];

            $params = $method->getParameters();

            foreach ($params as $param) {
//                dump($param);
                $operations[$op]['parameters'] = [
                    'object'  =>  $param->name,
                    'parameter' =>  $param->name,
                    'hint'  =>  isset($param->typeHint) ? $param->typeHint : 'null'
                ];
            }
        }

        return $operations;
    }

    public static function generate($module = [], callable $onProgress = null) {
        $timer = new Timer();

        $routes = Route::getRoutes();
        $savedAvailableRoutes = [];
        $count = 0;

        $timer->showDiff('GotRoutes');

        foreach ($routes as $route) {
            if(!array_key_exists('controller', $route->action))
                continue;

            try {
                if (!isset($route->action["controller"])) {
                    logger()->info('No controller info found for URL : '. $route->uri);
                    continue;
                }

                if($module) {
                    if(!Str::startsWith($route->action['controller'], $module)) {
                        Log::debug('[OptionsService@generate] Skipping controller: ' . $route->action['controller']);
                        continue;
                    }
                }

                $explodedRoute = explode('/', $route->uri);

                $implodedRoute = '';
                for($i = 0; $i < count($explodedRoute); $i++) {
                    if(Str::contains($explodedRoute[$i], '{'))
                        $implodedRoute .= ':object-id/';
                    else
                        $implodedRoute .= $explodedRoute[$i] . '/';
                }

                $implodedRoute = substr($implodedRoute, 0, strlen($implodedRoute) - 1);

                $controllerWithMethod = explode("@", $route->action["controller"]);
                $controller = $controllerWithMethod[0];

                if (!class_exists($controller)) {
                    logger()->info('Class could not be found : ' . $controller . ' - Route :' . $implodedRoute);
                    if ($onProgress) $onProgress('skip', $route->methods[0], $implodedRoute, 'class not found');
                    continue;
                }

                if (!method_exists($controller, $controllerWithMethod[1])) {
                    logger()->info('Method could not be found : ' . $controller . '@' .$controllerWithMethod[1] . ' - Route :' . $implodedRoute);
                    if ($onProgress) $onProgress('skip', $route->methods[0], $implodedRoute, 'method not found');
                    continue;
                }

                $timer->showDiff('StartingDocumentationWithReflection');

                $controllerInfo = new ReflectionClass($controller);
                $docComment = $controllerInfo->getDocComment();
                if ($docComment) {
                    $commentDescription = str_replace("\n", "", self::stripComment($docComment));
                } else {
                    $shortName = preg_replace('/Controller$/', '', $controllerInfo->getShortName());
                    $commentDescription = trim(implode(' ', preg_split('/(?=[A-Z])/', $shortName)));
                }

                $method = new ReflectionMethod($controller, $controllerWithMethod[1]);
                $methodDocComment = $method->getDocComment();
                $actionDescription = $methodDocComment ? str_replace("\n", "", self::stripComment($methodDocComment)) : '';

                $timer->showDiff('SavingTheRouteIfNotSaved');

                $newRoute = Requests::withTrashed()->firstOrNew([
                    'uri' => $implodedRoute,
                    'method' => $route->methods[0]
                ]);

                if ($newRoute->trashed()) {
                    $newRoute->restore();
                }

                $explodedController = explode('\\', $controller);
                $topic = $explodedController[0] === 'App'
                    ? ($explodedController[3] ?? '')
                    : ($explodedController[1] ?? '');

                $newRoute->method = $route->methods[0];
                $newRoute->controller = $controller;
                $newRoute->topic = $topic;
                $newRoute->controller_description = $commentDescription;
                $newRoute->action = $controllerWithMethod[1];
                $newRoute->action_description = $actionDescription;

                if(array_key_exists('middleware', $route->action))
                    $newRoute->middleware = $route->action["middleware"];

                $newRoute->search_filters = self::syncFilters($controller, $controllerWithMethod[1]);
                $newRoute->requests = self::syncRequests($controller, $controllerWithMethod[1], $implodedRoute);
                $newRoute->returns = self::syncReturns($controller, $controllerWithMethod[1]);
                $newRoute->save();

                $changes = [];
                if ($newRoute->wasChanged('search_filters')) $changes[] = 'search_filters';
                if ($newRoute->wasChanged('requests')) $changes[] = 'requests';
                if ($newRoute->wasChanged('returns')) $changes[] = 'returns';
                if ($newRoute->wasChanged('middleware')) $changes[] = 'middleware';
                if (!empty($changes)) {
                    ChangelogService::record($implodedRoute, $route->methods[0], $changes);
                }

                $timer->showDiff('SavedTheRoute');
                $timer->showDiff('StartingToGetLinkedObjects');

                self::getLinkedObjects($newRoute->uri);

                $timer->showDiff('GotLinkedObjects');

                $savedAvailableRoutes['routes'][$count]['uri'] = $implodedRoute;
                $savedAvailableRoutes['routes'][$count]['method'] = $route->methods[0];

                if ($onProgress) $onProgress('sync', $route->methods[0], $implodedRoute, null);

                $count++;
            } catch (\Throwable $e) {
                logger()->info($e);
                if ($onProgress) $onProgress('error', $route->methods[0] ?? '?', $route->uri ?? '?', $e->getMessage());
                continue;
            }
        }

        $timer->showDiff('CleanUpStarts');

        // Mevcut olmayan Route'ları bulup database'den siliyoruz
        $allRoutesFromDB = Requests::withTrashed()->select("uri", "method")->get();

        foreach ($allRoutesFromDB as $routeFromDB) {

            $found = false;

            foreach ($savedAvailableRoutes['routes'] ?? [] as $route) {
                if ($route['uri']==$routeFromDB->uri && $route['method']==$routeFromDB->method) {
                    $found = true;
                }
            }

            $route = Requests::withTrashed()->where("uri", $routeFromDB->uri)->where("method", $routeFromDB->method)->first();

//            if (!$found) {
//                if (!$route->trashed()) {
//                    $route->delete();
//                    logger()->info("Route is deleted: ". $routeFromDB->uri . " [". $routeFromDB->method ."]");
//                }
//            } else {
//                if ($route->trashed()) {
//                    $route->restore();
//                    logger()->info("Route is restored: ". $routeFromDB->uri . " [". $routeFromDB->method ."]");
//                }
//            }
        }
    }

    public static function createJSON(): string
    {
        $routes = Requests::all();

        $tags = $routes->pluck('topic')->unique()->filter()->values()->map(fn($t) => ['name' => $t])->toArray();

        $paths = [];
        foreach ($routes as $route) {
            $path = '/' . str_replace(':object-id', '{id}', $route->uri);
            $method = strtolower($route->method);
            $topic = $route->controller ? (explode('\\', $route->controller)[1] ?? '') : '';
            $middleware = $route->middleware ?? [];

            $parameters = [];
            if (str_contains($route->uri, ':object-id')) {
                $parameters[] = ['in' => 'path', 'name' => 'id', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'uuid']];
            }
            if ($route->method === 'GET' && $route->search_filters) {
                foreach ($route->search_filters as $filter) {
                    $parameters[] = [
                        'in'          => 'query',
                        'name'        => $filter['name'],
                        'required'    => false,
                        'description' => trim($filter['description'] ?? ''),
                        'schema'      => ['type' => $filter['type'] ?? 'string'],
                    ];
                }
            }

            $operation = [
                'tags'        => [$topic ?: 'General'],
                'summary'     => $route->action_description ?: '',
                'operationId' => lcfirst(str_replace(' ', '', ucwords(str_replace(['/', ':object-id', '-'], ' ', $route->uri)))) . ucfirst($method),
                'parameters'  => $parameters,
                'responses'   => [
                    '200' => ['description' => 'Successful operation'],
                    '401' => ['description' => 'Unauthorized'],
                    '403' => ['description' => 'Forbidden'],
                    '404' => ['description' => 'Not Found'],
                ],
            ];

            if (in_array('auth:api', $middleware) || in_array('auth:sanctum', $middleware)) {
                $operation['security'] = [['bearerAuth' => []]];
            }

            if (DeprecationService::isDeprecated($route->uri, $route->method)) {
                $operation['deprecated'] = true;
            }

            if (in_array($route->method, ['POST', 'PATCH', 'PUT']) && $route->requests) {
                $fields = $route->requests[0] ?? [];
                $properties = [];
                $required = [];
                foreach ($fields as $fieldName => $rules) {
                    $rulesArr = is_array($rules) ? $rules : explode('|', $rules);
                    $properties[$fieldName] = ['type' => 'string'];
                    if (in_array('required', $rulesArr)) $required[] = $fieldName;
                }
                $operation['requestBody'] = [
                    'required' => true,
                    'content'  => [
                        'application/json' => [
                            'schema' => array_filter([
                                'type'       => 'object',
                                'properties' => $properties,
                                'required'   => $required ?: null,
                            ])
                        ]
                    ]
                ];
            }

            $paths[$path][strtolower($route->method)] = $operation;
        }

        $spec = [
            'openapi' => '3.0.3',
            'info'    => [
                'title'       => 'PlusClouds API',
                'description' => 'Auto-generated API documentation',
                'version'     => '1.0.0',
                'contact'     => ['email' => 'admin@plusclouds.com'],
            ],
            'servers' => [
                ['url' => config('app.url'), 'description' => 'Current environment'],
            ],
            'tags'   => $tags,
            'paths'  => $paths,
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT'],
                ],
            ],
        ];

        return json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    private static function buildCurlSnippet(string $method, string $uri, ?Requests $options): string
    {
        $url = rtrim(config('app.url'), '/') . '/' . $uri;
        $headers = "-H 'Accept: application/json'";

        $middleware = $options ? ($options->middleware ?? []) : [];
        if (in_array('auth:api', $middleware) || in_array('auth:sanctum', $middleware)) {
            $headers .= " \\\n  -H 'Authorization: Bearer {token}'";
        }

        $body = '';
        if (in_array($method, ['POST', 'PATCH', 'PUT']) && $options && $options->requests) {
            $fields = $options->requests[0] ?? [];
            $example = array_fill_keys(array_keys($fields), '');
            $body = " \\\n  -H 'Content-Type: application/json' \\\n  -d '" . json_encode($example) . "'";
        }

        return "curl -X {$method} '{$url}' \\\n  {$headers}{$body}";
    }

    /**
     * Retrieves the return values of the given method of class
     */
    public static function syncReturns($classname, $method)
    {
        $transformer = null;

        if (!method_exists($classname, $method)) {
            return null;
        }

        $method = new ReflectionMethod($classname, $method);

        $transformer = self::getTransformer($method->getDocComment());

        //  If no transformer exists then return null
        if ($transformer == null) {
            return null;
        }

        try {
            $transformer = new ReflectionClass($transformer);
        } catch (\Exception $e) {
            return null;
        }

        $properties = $transformer->getProperties();

        $data = [];

        for ($j = 0; $j < count($properties); $j++) {
            $property = $properties[$j];

            $property->setAccessible(true);

            switch ($property->getName()) {
                case 'visible':
                    $data['visible'] = $property->getValue($transformer->newInstanceArgs([]));
                    break;
                case 'defaultIncludes':
                    $data['defaultIncludes'] = $property->getValue($transformer->newInstanceArgs([]));
                    break;
                case 'availableIncludes':
                    $data['availableIncludes'] = $property->getValue($transformer->newInstanceArgs([]));
                    break;
            }
        }

        $data['values'] = [];

        return $data;
    }

    /**
     * Retrieves the request parameters of the given method of class
     */
    public static function syncRequests($classname, $method, $route)
    {
        $params = null;

        $result = [];

        if (!method_exists($classname, $method)) {
            return null;
        }

        $method = new ReflectionMethod($classname, $method);

        $params = $method->getParameters();

        foreach ($params as $param) {
            if ($param->getName() == "request") {
                try {
                    $class = $param->getType()->getName();
                    $request = new ReflectionMethod($class, "rules");
                    $result[] = $request->invoke(new $class);
                } catch (\Exception $e) {
                    logger()->info('Method [rules] cannot be found on class '. $class . ' when retrieving Requests for route '. $route);
                    continue;
                }
            }
        }

        if (empty($result)) {
            return null;
        } else {
            return $result;
        }
    }

    /**
     * Retrieves the filters applied to the given method of class
     */
    public static function syncFilters($classname, $method)
    {
        $filters = [];

        $filtersFound = [];

        $params = null;


        if (!method_exists($classname, $method)) {
            return null;
        }

        $method = new ReflectionMethod($classname, $method);

        $params = $method->getParameters();

        $filter = null;

        for ($j = 0; $j < count($params); $j++) {
            $paramType = $params[$j]->getType();

            if ($paramType == null) {
                continue;
            }

            if ($paramType->getName() == 'Illuminate\Http\Request') {
                continue;
            }

            if (! (strpos($paramType->getName(), 'Filter') !== false)) {
                continue;
            }

            //  We found the parameter. Now we find the functions in the content of the parameter

            try {
                $filterName = $paramType->getName();

                if (! array_key_exists($filterName, $filters)) {
                    $filters[$filterName] = new ReflectionClass($paramType->getName());
                }

                $filter = $filters[$filterName];
            } catch (\Exception $e) {
                continue;
            }

            $filterMethods = $filter->getMethods();

            for ($k = 0; $k < count($filterMethods); $k++) {
                if ($filterMethods[$k]->class == 'NextDeveloper\Commons\Database\Filters\AbstractQueryFilter') {
                    continue;
                }

                //  Lets parse the functions

                $filtersFound[] = [
                    'name'          =>  $filterMethods[$k]->getName(),
                    'type'          =>  self::getFilterParamType($filterMethods[$k]->getDocComment()),
                    'description'   =>  self::stripComment($filterMethods[$k]->getDocComment())
                ];
            }
        }
        if (empty($filtersFound)) {
            return null;
        } else {
            return $filtersFound;
        }
    }

    public static function stripComment($comment)
    {
        $commentLine = explode(PHP_EOL, $comment);

        $strippedComment = '';

        for ($i = 0; $i < count($commentLine); $i++) {
            if (trim($commentLine[$i]) == '/**' ||
                trim($commentLine[$i]) == '*/' ||
                trim($commentLine[$i]) == '*' ||
                strpos($commentLine[$i], ' * Class') !== false ||
                strpos($commentLine[$i], ' * @') !== false
            ) {
                continue;
            }

            $commentLine[$i] = trim(str_replace(' * ', '', $commentLine[$i]));

            $strippedComment .= $commentLine[$i] . PHP_EOL;
        }

        return $strippedComment;
    }

    public static function getFilterParamType($comment)
    {
        $commentLine = explode(PHP_EOL, $comment);

        $strippedComment = '';

        for ($i = 0; $i < count($commentLine); $i++) {
            if (strpos($commentLine[$i], ' * @param') !== false) {
                $strippedComment = $commentLine[$i];
                $strippedComment = trim($strippedComment);
                $strippedComment = str_replace('* ', '', $strippedComment);

                $strippedComment = (explode(' ', $strippedComment)) [1];

                return $strippedComment;
            }
        }
    }

    public static function getTransformer($comment)
    {
        $commentLine = explode(PHP_EOL, $comment);

        $returnValue = '';

        for ($i = 0; $i < count($commentLine); $i++) {
            if (strpos($commentLine[$i], ' * @return') !== false) {
                $returnValue = trim($commentLine[$i]);
                $returnValue = trim(str_replace('* @return', '', $returnValue));

                $is_array = (strpos($returnValue, '|') !== false);

                if ($is_array) {
                    $returnVals = explode('|', $returnValue);

                    foreach ($returnVals as $returnVal) {
                        if (strpos($returnVal, 'Transformer')) {
                            return $returnVal;
                        }
                    }
                }

                break;
            }
        }

        return null;
    }

    private static function buildGetRequest($method)
    {
        $middlewares = json_decode($method['middleware']);

        $request['description'] = $method['action_description'];

        if ($method['search_filters']) {
            $request['search_filters'] = [
                'uri'           =>  $method['uri'],
                'parameters'    =>  json_decode($method['search_filters'])
            ];
        }

        if($middlewares){
            if (in_array('api', $middlewares)) {
                $request['returns']   =  'json|array';
            }

            if (in_array('auth:api', $middlewares)) {
                $request['authentication']   =  'required';
            }
        }

        $request['returns'] = 'array';

        return $request;
    }

    private static function buildPostRequest($method)
    {
        return [
            'fields'    =>  json_decode($method['requests'])
        ];
    }

    private static function getDirectories($dir)
    {
        $dirs = Requests::where('uri', 'ilike', $dir . '%')->get();

        $foundDirs = [
            '.'
        ];

        if ($dir != 'v2/') {
            $foundDirs[] = '..';
        }

        for ($i = 0; $i < count($dirs); $i++) {
            $subDir = $dirs[$i]['uri'];

            /**
             * Here this if is a workaround, because when we remove actual directory from
             * subdirectory then we remove with / but with v2 there is / in the begining.
             *
             * That is why do not remove this workaround!!!
             */
            if ($dir != 'v2/') {
                $subDir = str_replace($dir, '', $subDir);
            }

            $explodedSubDir = explode('/', $subDir);

            if (! array_key_exists(1, $explodedSubDir)) {
                continue;
            }

            $subDir = $explodedSubDir[1];

            if (count($foundDirs) == 0) {
                $foundDirs[] = $subDir;
            }

            if (!in_array($subDir, $foundDirs)) {
                $foundDirs[] = $subDir;
            }
        }

        $linkedObjects = Requests::where('uri', 'ilike', $dir . '%')->first();

        if(!$linkedObjects)
            return null;

        $linkedObjects = $linkedObjects->linked_objects;

        $foundDirs = array_merge(
            $foundDirs,
            //self::getLinkedObjects($dir)
        );

        return $foundDirs;
    }

    public static function getLinkedObjects($route) {
        $timer = new Timer();
        Log::info('Running for uri: ' . $route);

        $explodedRoute = explode('/', $route);

        $route = Requests::where('uri', $explodedRoute[0] . '/' . $explodedRoute[1])
            ->where('method', 'GET')
            ->where('updated_at', '<=', Carbon::now()->subSecond(15)->toDateTimeString())
            ->first();

        if(!$route)
            return [];

        $explodedRoute = explode('/', $route->uri);

        $controller = $route->controller;
        $explodedController = explode('\\', $controller);
        $namespace = $explodedController[0];
        $module = $explodedController[1];

        $modelName = $namespace . '\\' . $module . '\\Database\\Models\\' . Str::ucfirst(Str::camel($explodedRoute[1]));

        $timer->showDiff('GotModelName');

        try {
            $model = new ReflectionClass($modelName);
        } catch (ReflectionException $exception) {
            return null;
        }

        $timer->showDiff('IHaveTheReflectionModel');

        $ourMethods = [];
        $rawMethods = [];

        $i = 0;

        $methods = $model->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $timer->showDiff('ModelLoop PreStarts ' . $i . ' Times');

            $i++;

            if($method->getReturnType() == null) {
                Log::info('[OptionsService@getLinkedObjects] Return type is null. Continue;');
                continue;
            }
            //  Relational functions cannot be public static, that is why we pass this.
            if($method->getModifiers() == 17) {
                Log::info('[OptionsService@getLinkedObjects] Function is public static, it should be public. Continue;');
                continue;
            }

            if($method->class != $modelName) {
                Log::info('[OptionsService@getLinkedObjects] Class is another class. Continue;');
                continue;
            }

            $timer->showDiff('ModelLoop Starts');

            $returnTypeName = $method->getReturnType()->getName();

            if(
                $returnTypeName === 'Illuminate\Database\Eloquent\Relations\BelongsTo' ||
                $returnTypeName === 'Illuminate\Database\Eloquent\Relations\HasMany'
            ) {
                $ourMethod = str_replace('_', '-', Str::snake($method->name));
                $ourMethods[] = ':object-id/' . $ourMethod;

                $type = 'hasMany';

                if( $returnTypeName === 'Illuminate\Database\Eloquent\Relations\BelongsTo' ) {
                    $type = 'belongsTo';
                }

                $rawMethods[] = [
                    'type'      =>  $type,
                    'method'    =>  $ourMethod,
                    'module'    =>  $module,
                    'model'     =>  Str::camel($explodedRoute[1]),
                    'modelFullPath' =>  $modelName
                ];
            }

            $timer->showDiff('ModelLoop Finish: ' . $i);
        }

        $route->update([
            'linked_objects'    =>  $rawMethods
        ]);

        return $ourMethods;
    }
}
