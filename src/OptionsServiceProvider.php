<?php

namespace NextDeveloper\Options;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use NextDeveloper\Commons\AbstractServiceProvider;
use NextDeveloper\Options\Services\OptionsService;

/**
 * Class OptionsServiceProvider
 *
 * @package NextDeveloper\Options
 */
class OptionsServiceProvider extends AbstractServiceProvider
{
    /**
     * @var bool
     */
    protected $defer = false;

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(
            [
            __DIR__.'/../config/options.php' => config_path('options.php'),
            ], 'config'
        );

        $this->loadViewsFrom($this->dir.'/../resources/views', 'Options');

        //        $this->bootErrorHandler();
        $this->bootChannelRoutes();
        $this->bootModelBindings();
        $this->bootEvents();
        $this->bootLogger();
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->registerHelpers();
        $this->registerMiddlewares('generator');
        $this->registerRoutes();
        $this->registerCommands();

        $this->mergeConfigFrom(__DIR__.'/../config/options.php', 'options');
        $this->customMergeConfigFrom(__DIR__.'/../config/relation.php', 'relation');
    }

    /**
     * @return void
     */
    public function bootLogger()
    {
        //        $monolog = Log::getMonolog();
        //        $monolog->pushProcessor(new \Monolog\Processor\WebProcessor());
        //        $monolog->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());
        //        $monolog->pushProcessor(new \Monolog\Processor\MemoryPeakUsageProcessor());
    }

    /**
     * @return array
     */
    public function provides()
    {
        return ['generator'];
    }

    //    public function bootErrorHandler() {
    //        $this->app->singleton(
    //            ExceptionHandler::class,
    //            Handler::class
    //        );
    //    }

    /**
     * @return void
     */
    private function bootChannelRoutes()
    {
        if (file_exists(($file = $this->dir.'/../config/channel.routes.php'))) {
            include_once $file;
        }
    }

    /**
     * @return void
     */
    protected function bootEvents()
    {
        $configs = config()->all();

        foreach ($configs as $key => $value) {
            if (config()->has($key.'.events')) {
                foreach (config($key.'.events') as $event => $handlers) {
                    foreach ($handlers as $handler) {
                        $this->app['events']->listen($event, $handler);
                    }
                }
            }
        }
    }

    /**
     * Register module routes
     *
     * @return void
     */
    protected function registerRoutes()
    {
        if (! $this->app->routesAreCached()) {
            $this->app['router']
                ->namespace('NextDeveloper\Options\Http\Controllers')
                ->group(__DIR__.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'api.routes.php');
        }

        Route::options('{any}',function () {
            return OptionsService::getOptions( Request::getRequestUri() );
        })->where('any', '.*');

//        Route::options( '/', 'OptionsController@options' );
//        Route::options( '{a}', 'OptionsController@options' )->where(['a' => '.*']);
//        Route::options( '{a}/{b}', 'OptionsController@options' )->where(['a' => '.*', 'b' => '.*']);
//        Route::options( '{a}/{b}/{c}', 'OptionsController@options' )->where(['a' => '.*', 'b' => '.*', 'c' => '.*']);
//        Route::options( '{a}/{b}/{c}/{d}', 'OptionsController@options' )->where(['a' => '.*', 'b' => '.*', 'c' => '.*', 'd' => '.*']);
//        Route::options( '{a}/{b}/{c}/{d}/{e}', 'OptionsController@options' )->where(['a' => '.*', 'b' => '.*', 'c' => '.*', 'd' => '.*', 'e' => '.*']);
//        Route::options( '{a}/{b}/{c}/{d}/{e}/{f}', 'OptionsController@options' )->where(['a' => '.*', 'b' => '.*', 'c' => '.*', 'd' => '.*', 'e' => '.*', 'f' => '.*']);
//        Route::options( '{a}/{b}/{c}/{d}/{e}/{f}/{g}', 'OptionsController@options' )->where(['a' => '.*', 'b' => '.*', 'c' => '.*', 'd' => '.*', 'e' => '.*', 'f' => '.*', 'g' => '.*']);
    }

    /**
     * Registers module based commands
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [

                ]
            );
        }
    }

    /**
     * This is here, in case of shit happens!
     *
     * @return void
     */
    private function checkDatabaseConnection()
    {
        $isSuccessfull = false;

        try {
            \DB::connection()->getPdo();

            $isSuccessfull = true;
        } catch (\Exception $e) {
            die('Could not connect to the database. Please check your configuration. error:'.$e);
        }

        return $isSuccessfull;
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}