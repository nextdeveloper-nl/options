<?php

namespace NextDeveloper\Options\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\Options\Database\Observers\RequestsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;

/**
 * Requests model.
 *
 * @package  NextDeveloper\Options\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property string $uri
 * @property string $method
 * @property string $controller
 * @property string $topic
 * @property string $controller_description
 * @property $action
 * @property string $action_description
 * @property $middleware
 * @property $search_filters
 * @property $requests
 * @property $returns
 * @property $linked_objects
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class Requests extends Model
{
    use Filterable, UuidId, CleanCache, Taggable;
    use SoftDeletes;


    public $timestamps = true;

    protected $table = 'option_requests';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            'uri',
            'method',
            'controller',
            'topic',
            'controller_description',
            'action',
            'action_description',
            'middleware',
            'search_filters',
            'requests',
            'returns',
            'linked_objects',
    ];

    /**
      Here we have the fulltext fields. We can use these for fulltext search if enabled.
     */
    protected $fullTextFields = [

    ];

    /**
     @var array
     */
    protected $appends = [

    ];

    /**
     We are casting fields to objects so that we can work on them better
     *
     @var array
     */
    protected $casts = [
    'id' => 'integer',
    'uri' => 'string',
    'method' => 'string',
    'controller' => 'string',
    'topic' => 'string',
    'controller_description' => 'string',
    'action_description' => 'string',
    'middleware' => 'array',
    'search_filters' => 'array',
    'requests' => 'array',
    'returns' => 'array',
    'linked_objects' => 'array',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
    ];

    /**
     We are casting data fields.
     *
     @var array
     */
    protected $dates = [
    'created_at',
    'updated_at',
    'deleted_at',
    ];

    /**
     @var array
     */
    protected $with = [

    ];

    /**
     @var int
     */
    protected $perPage = 20;

    /**
     @return void
     */
    public static function boot()
    {
        parent::boot();

        //  We create and add Observer even if we wont use it.
        parent::observe(RequestsObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('options.scopes.global');
        $modelScopes = config('options.scopes.option_requests');

        if(!$modelScopes) { $modelScopes = [];
        }
        if (!$globalScopes) { $globalScopes = [];
        }

        $scopes = array_merge(
            $globalScopes,
            $modelScopes
        );

        if($scopes) {
            foreach ($scopes as $scope) {
                static::addGlobalScope(app($scope));
            }
        }
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n


















}
