<?php

namespace NextDeveloper\Options\Services\AbstractServices;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\Commons\Helpers\DatabaseHelper;
use NextDeveloper\Options\Database\Models\Requests;
use NextDeveloper\Options\Database\Filters\RequestsQueryFilter;
use NextDeveloper\Options\Events\Requests\RequestsCreatedEvent;
use NextDeveloper\Options\Events\Requests\RequestsCreatingEvent;
use NextDeveloper\Options\Events\Requests\RequestsUpdatedEvent;
use NextDeveloper\Options\Events\Requests\RequestsUpdatingEvent;
use NextDeveloper\Options\Events\Requests\RequestsDeletedEvent;
use NextDeveloper\Options\Events\Requests\RequestsDeletingEvent;


/**
 * This class is responsible from managing the data for Requests
 *
 * Class RequestsService.
 *
 * @package NextDeveloper\Options\Database\Models
 */
class AbstractRequestsService
{
    public static function get(RequestsQueryFilter $filter = null, array $params = []) : Collection|LengthAwarePaginator
    {
        $enablePaginate = array_key_exists('paginate', $params);

        /**
        * Here we are adding null request since if filter is null, this means that this function is called from
        * non http application. This is actually not I think its a correct way to handle this problem but it's a workaround.
        *
        * Please let me know if you have any other idea about this; baris.bulut@nextdeveloper.com
        */
        if($filter == null) {
            $filter = new RequestsQueryFilter(new Request());
        }

        $perPage = config('commons.pagination.per_page');

        if($perPage == null) {
            $perPage = 20;
        }

        if(array_key_exists('per_page', $params)) {
            $perPage = intval($params['per_page']);

            if($perPage == 0) {
                $perPage = 20;
            }
        }

        if(array_key_exists('orderBy', $params)) {
            $filter->orderBy($params['orderBy']);
        }

        $model = Requests::filter($filter);

        if($model && $enablePaginate) {
            return $model->paginate($perPage);
        } else {
            return $model->get();
        }
    }

    public static function getAll()
    {
        return Requests::all();
    }

    /**
     * This method returns the model by looking at reference id
     *
     * @param  $ref
     * @return mixed
     */
    public static function getByRef($ref) : ?Requests
    {
        return Requests::findByRef($ref);
    }

    /**
     * This method returns the model by lookint at its id
     *
     * @param  $id
     * @return Requests|null
     */
    public static function getById($id) : ?Requests
    {
        return Requests::where('id', $id)->first();
    }

    /**
     * This method created the model from an array.
     *
     * Throws an exception if stuck with any problem.
     *
     * @param  array $data
     * @return mixed
     * @throw  Exception
     */
    public static function create(array $data)
    {
        event(new RequestsCreatingEvent());

                
        try {
            $model = Requests::create($data);
        } catch(\Exception $e) {
            throw $e;
        }

        event(new RequestsCreatedEvent($model));

        return $model->fresh();
    }

    /**
     This function expects the ID inside the object.
    
     @param  array $data
     @return Requests
     */
    public static function updateRaw(array $data) : ?Requests
    {
        if(array_key_exists('id', $data)) {
            return self::update($data['id'], $data);
        }

        return null;
    }

    /**
     * This method updated the model from an array.
     *
     * Throws an exception if stuck with any problem.
     *
     * @param
     * @param  array $data
     * @return mixed
     * @throw  Exception
     */
    public static function update($id, array $data)
    {
        $model = Requests::where('uuid', $id)->first();

        
        event(new RequestsUpdatingEvent($model));

        try {
            $isUpdated = $model->update($data);
            $model = $model->fresh();
        } catch(\Exception $e) {
            throw $e;
        }

        event(new RequestsUpdatedEvent($model));

        return $model->fresh();
    }

    /**
     * This method updated the model from an array.
     *
     * Throws an exception if stuck with any problem.
     *
     * @param
     * @param  array $data
     * @return mixed
     * @throw  Exception
     */
    public static function delete($id, array $data)
    {
        $model = Requests::where('uuid', $id)->first();

        event(new RequestsDeletingEvent());

        try {
            $model = $model->delete();
        } catch(\Exception $e) {
            throw $e;
        }

        event(new RequestsDeletedEvent($model));

        return $model;
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

}
