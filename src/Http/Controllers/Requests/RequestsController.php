<?php

namespace NextDeveloper\Options\Http\Controllers\Requests;

use Illuminate\Http\Request;
use NextDeveloper\Options\Http\Controllers\AbstractController;
use NextDeveloper\Generator\Http\Traits\ResponsableFactory;
use NextDeveloper\Options\Http\Requests\Requests\RequestsUpdateRequest;
use NextDeveloper\Options\Database\Filters\RequestsQueryFilter;
use NextDeveloper\Options\Services\RequestsService;
use NextDeveloper\Options\Http\Requests\Requests\RequestsCreateRequest;

class RequestsController extends AbstractController
{
    /**
     * This method returns the list of requests.
     *
     * optional http params:
     * - paginate: If you set paginate parameter, the result will be returned paginated.
     *
     * @param  RequestsQueryFilter $filter  An object that builds search query
     * @param  Request             $request Laravel request object, this holds all data about request. Automatically populated.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(RequestsQueryFilter $filter, Request $request)
    {
        $data = RequestsService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    /**
     * This method receives ID for the related model and returns the item to the client.
     *
     * @param  $requestsId
     * @return mixed|null
     * @throws \Laravel\Octane\Exceptions\DdException
     */
    public function show($ref)
    {
        //  Here we are not using Laravel Route Model Binding. Please check routeBinding.md file
        //  in NextDeveloper Platform Project
        $model = RequestsService::getByRef($ref);

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method created Requests object on database.
     *
     * @param  RequestsCreateRequest $request
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function store(RequestsCreateRequest $request)
    {
        $model = RequestsService::create($request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method updates Requests object on database.
     *
     * @param  $requestsId
     * @param  CountryCreateRequest $request
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function update($requestsId, RequestsUpdateRequest $request)
    {
        $model = RequestsService::update($requestsId, $request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method updates Requests object on database.
     *
     * @param  $requestsId
     * @param  CountryCreateRequest $request
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function destroy($requestsId)
    {
        $model = RequestsService::delete($requestsId);

        return ResponsableFactory::makeResponse($this, $model);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

}