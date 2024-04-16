<?php

namespace NextDeveloper\Options\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use NextDeveloper\Commons\Common\Timer\Timer;
use NextDeveloper\Options\Database\Models\Requests;
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

    public static function generate($module = []) {
        // Tüm route bilgilerini alıyoruz
        $timer = new Timer();

        $routes = Route::getRoutes();
        $savedAvailableRoutes = [];
        $count = 0;

        $timer->showDiff('GotRoutes');

        foreach ($routes as $route) {
            try {
                if (!isset($route->action["controller"])) {
                    logger()->info('No controller info found for URL : '. $route->uri);
                    continue;
                }

                if($module) {
                    if(!Str::startsWith($route->action['controller'], $module)) {
                        Log::info('[OptionsService@generate] Not the controller we want. Skipping this controller:'
                            . ' ' . $route->action['controller']);
                        continue;
                    }
                }

                /**
                 * Here since we have a standart we split the uri like this;
                 * 0: module
                 * 1: object
                 * 2: id of object
                 * 3: related object for id
                 *
                 * However we may have changed in general, so we create a generic parser and convert the variable
                 * to :object-id text
                 */
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

                // Skip and log the route when controller could not be found

                if (!class_exists($controller)) {
                    logger()->info('Class could not be found : ' . $controller . ' - Route :' . $implodedRoute);
                    continue;
                }

                // Skip and log the route when method could not be found

                if (!method_exists($controller, $controllerWithMethod[1])) {
                    logger()->info('Method could not be found : ' . $controller . '@' .$controllerWithMethod[1] . ' - Route :' . $implodedRoute);
                    continue;
                }

                $timer->showDiff('StartingDocumentationWithReflection');

                $controllerInfo = new ReflectionClass($controller);
                $commentDescription = str_replace("\n", "", self::stripComment($controllerInfo->getDocComment()));

                $method = new ReflectionMethod($controller, $controllerWithMethod[1]);
                $actionDescription = str_replace("\n", "", self::stripComment($method->getDocComment()));

                // Route daha önce kaydedilmemişse ediyoruz

                $timer->showDiff('SavingTheRouteIfNotSaved');

                $newRoute = Requests::withTrashed()->firstOrNew([
                    'uri' => $implodedRoute,
                    'method' => $route->methods[0]
                ]);

                if ($newRoute->trashed()) {
                    $newRoute->restore();
                }

                $newRoute->method = $route->methods[0];
                $newRoute->controller = $controller;
                $newRoute->topic = '';
                $newRoute->controller_description = $commentDescription;
                $newRoute->action = $controllerWithMethod[1];
                $newRoute->action_description = $actionDescription;

                if(array_key_exists('middleware', $route->action))
                    $newRoute->middleware = stripslashes(json_encode($route->action["middleware"], JSON_UNESCAPED_SLASHES));

                $newRoute->search_filters = self::syncFilters($controller, $controllerWithMethod[1]);
                $newRoute->requests = self::syncRequests($controller, $controllerWithMethod[1], $implodedRoute);
                $newRoute->returns = self::syncReturns($controller, $controllerWithMethod[1]);
                $newRoute->save();

                $timer->showDiff('SavedTheRoute');
                $timer->showDiff('StartingToGetLinkedObjects');

                //self::getLinkedObjects($newRoute->uri);

                $timer->showDiff('GotLinkedObjects');

                // Route URL'lerini bir array'a topluyoruz, daha sonra database ile karşılaştırma yapmak üzere

                $savedAvailableRoutes['routes'][$count]['uri'] = $implodedRoute;
                $savedAvailableRoutes['routes'][$count]['method'] = $route->methods[0];

                $count++;
            } catch (\Throwable $e) {
                dump($e);
                dump($controllerWithMethod);
                dd($route);

                logger()->info($e);
                continue;
            }
        }

        $timer->showDiff('CleanUpStarts');

        // Mevcut olmayan Route'ları bulup database'den siliyoruz
        $allRoutesFromDB = Requests::withTrashed()->select("uri", "method")->get();

        foreach ($allRoutesFromDB as $routeFromDB) {

            // Database'deli route daha önce topladığımız arrayda mevcut mu kontrol ediyoruz
            $found = false;

            foreach ($savedAvailableRoutes['routes'] as $route) {
                if ($route['uri']==$routeFromDB->uri && $route['method']==$routeFromDB->method) {
                    $found = true;
                }
            }

            $route = Requests::withTrashed()->where("uri", $routeFromDB->uri)->where("method", $routeFromDB->method)->first();

            if (!$found) {
                if (!$route->trashed()) {
                    $route->delete();
                    logger()->info("Route was deleted: ". $routeFromDB->uri . " [". $routeFromDB->method ."]");
                }
            } else {
                if ($route->trashed()) {
                    $route->restore();
                    logger()->info("Route was restored: ". $routeFromDB->uri . " [". $routeFromDB->method ."]");
                }
            }
        }
    }

    public static function createJSON()
    {
        $routesForTags = Requests::get();
        $routesForPaths = $routesForTags;
        $json = array();
        $tags = array();

        foreach ($routesForTags as $route) {
            $controllerArray = explode('\\', $route->controller);
            $tags[] = $controllerArray[1];
        }

        $tags = array_unique($tags);

        $json['swagger'] = "2.0";
        $json['info']['description'] = "fdsfds";
        $json['info']['version'] = "1.0.0";
        $json['info']['title'] = "PlusClouds API Documentation";
        $json['info']['contact']['email'] = 'admin@pluslouds.com';
        $json['host'] = "plusclouds.com";
        $json['basePath'] = "/v2";

        $count = 0;
        foreach ($tags as $tag) {
            $json['tags'][$count]['name'] = $tag;
            $count++;
        }

        $json['schemes'][] = "https";

        foreach ($routesForPaths as $route) {
            $controllerArray = explode('\\', $route->controller);
            $removeV2 = $route->uri;
            $json['paths'][$removeV2][strtolower($route->method)]['tags'] = array($controllerArray[1]);
            $json['paths'][$removeV2][strtolower($route->method)]['summary'] = array($route->action_description);
            if (strpos($removeV2, '{') !== false) {
                $json['paths'][$removeV2][strtolower($route->method)]['parameters'] = array();
                $regex = '/{\K[^}]*(?=})/m';
                preg_match_all($regex, $removeV2, $matches);
                foreach ($matches[0] as $match) {
                    $json['paths'][$removeV2][strtolower($route->method)]['parameters'][] = array('in'=>'path', 'name'=> $match, 'required' => true, 'schema'=>array('$ref'=>""));
                }
            }
            if (!is_null($route->requests)) {
                $requests = json_decode($route->requests, true);
                if (!empty($requests)) {
                    if (!isset($json['paths'][$removeV2][strtolower($route->method)]['parameters'])) {
                        $json['paths'][$removeV2][strtolower($route->method)]['parameters'] = array();
                    }
                    foreach ($requests[0] as $key => $item) {
                        $required = false;
                        if ((is_array($item) && in_array('required', $item))) {
                            $required = true;
                        }
                        if (!is_array($item)) {
                            $parameterArray = explode('|', $item);
                            if (in_array("required", $parameterArray)) {
                                $required = true;
                            }
                        }
                        $json['paths'][$removeV2][strtolower($route->method)]['parameters'][] = array('in'=>'query', 'name'=> $key, 'required' => $required, 'schema'=>array('$ref'=>""));
                    }
                }
            }

            $exludedRoutes = array(

            );

            switch ($route->method) {

                case "GET":

                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['200']['description'] = 'successful operation';

                    if (!in_array($removeV2, $exludedRoutes)) {
                        $json['paths'][$removeV2][strtolower($route->method)]['responses']['401']['description'] = 'Unauthorized';
                        $json['paths'][$removeV2][strtolower($route->method)]['responses']['403']['description'] = 'Forbidden';
                    }

                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['404']['description'] = 'Not Found';

                    break;

                case "POST":

                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['200']['description'] = 'successful operation';

                    if (!in_array($removeV2, $exludedRoutes)) {
                        $json['paths'][$removeV2][strtolower($route->method)]['responses']['401']['description'] = 'Unauthorized';
                        $json['paths'][$removeV2][strtolower($route->method)]['responses']['403']['description'] = 'Forbidden';
                    }

                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['404']['description'] = 'Not Found';
                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['201']['description'] = 'Created';
                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['422']['description'] = 'Unprocessable Entity';

                    break;

                case "PUT":

                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['200']['description'] = 'successful operation';

                    if (!in_array($removeV2, $exludedRoutes)) {
                        $json['paths'][$removeV2][strtolower($route->method)]['responses']['401']['description'] = 'Unauthorized';
                        $json['paths'][$removeV2][strtolower($route->method)]['responses']['403']['description'] = 'Forbidden';
                    }

                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['404']['description'] = 'Not Found';
                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['422']['description'] = 'Unprocessable Entity';
                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['204']['description'] = 'No Content';

                    break;

                case "DELETE":

                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['200']['description'] = 'successful operation';

                    if (!in_array($removeV2, $exludedRoutes)) {
                        $json['paths'][$removeV2][strtolower($route->method)]['responses']['401']['description'] = 'Unauthorized';
                        $json['paths'][$removeV2][strtolower($route->method)]['responses']['403']['description'] = 'Forbidden';
                    }

                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['404']['description'] = 'Not Found';
                    $json['paths'][$removeV2][strtolower($route->method)]['responses']['204']['description'] = 'No Content';

                    break;

            }
        }

        return json_encode($json);
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

        return json_encode($data);
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
            return json_encode($result);
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
            return json_encode($filtersFound);
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
        $dirs = Requests::where('uri', 'like', $dir . '%')->get();

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

        $linkedObjects = Requests::where('uri', 'like', $dir . '%')->first();

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

            if($i > 288)
                $a = 1;

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

            if(
                $method->getReturnType() == 'Illuminate\Database\Eloquent\Relations\BelongsTo' ||
                $method->getReturnType() == 'Illuminate\Database\Eloquent\Relations\HasMany'
            ) {
                $ourMethod = str_replace('_', '-', Str::snake($method->name));
                $ourMethods[] = ':object-id/' . $ourMethod;

                $type = 'hasMany';

                if( $method->getReturnType() == 'Illuminate\Database\Eloquent\Relations\BelongsTo' ) {
                    $type = 'belongsTo';
                }

                $rawMethods[] = [
                    'type'      =>  $type,
                    'method'    =>  $ourMethod,
                    'module'    =>  $module,
                    'model'     =>  Str::camel($explodedRoute[1])
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
