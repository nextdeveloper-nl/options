<?php

namespace NextDeveloper\Options\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\Options\Database\Observers\RequestsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;

/**
 * Class Requests.
 *
 * @package NextDeveloper\Options\Database\Models
 */
class Requests extends Model
{
    use Filterable, UuidId;
    use SoftDeletes;


    public $timestamps = true;

    protected $table = 'option_requests';


    /**
     @var array
     */
    protected $guarded = [];

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
    'id'                     => 'integer',
    'uuid'                   => 'string',
    'uri'                    => 'string',
    'method'                 => 'string',
    'controller'             => 'string',
    'topic'                  => 'string',
    'controller_description' => 'string',
    'action'                 => 'string',
    'action_description'     => 'string',
    'middleware'             => 'string',
    'search_filters'         => 'string',
    'requests'               => 'string',
    'returns'                => 'string',
    'created_at'             => 'datetime',
    'updated_at'             => 'datetime',
    'deleted_at'             => 'datetime',
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

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE\n\n\n\n\n\n\n\n\n\n\n\n\n\n
}