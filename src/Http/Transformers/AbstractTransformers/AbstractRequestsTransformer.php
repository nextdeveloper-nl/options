<?php

namespace NextDeveloper\Options\Http\Transformers\AbstractTransformers;

use NextDeveloper\Options\Database\Models\Requests;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;

/**
 * Class RequestsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\Options\Http\Transformers
 */
class AbstractRequestsTransformer extends AbstractTransformer
{

    /**
     * @param Requests $model
     *
     * @return array
     */
    public function transform(Requests $model)
    {
            
        return $this->buildPayload(
            [
            'id'  =>  $model->uuid,
            'uri'  =>  $model->uri,
            'method'  =>  $model->method,
            'controller'  =>  $model->controller,
            'topic'  =>  $model->topic,
            'controller_description'  =>  $model->controller_description,
            'action'  =>  $model->action,
            'action_description'  =>  $model->action_description,
            'middleware'  =>  $model->middleware,
            'search_filters'  =>  $model->search_filters,
            'requests'  =>  $model->requests,
            'returns'  =>  $model->returns,
            'linked_objects'  =>  $model->linked_objects,
            'created_at'  =>  $model->created_at ? $model->created_at->toIso8601String() : null,
            'updated_at'  =>  $model->updated_at ? $model->updated_at->toIso8601String() : null,
            'deleted_at'  =>  $model->deleted_at ? $model->deleted_at->toIso8601String() : null,
            ]
        );
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE\n\n\n\n\n\n\n\n\n\n\n\n






}
