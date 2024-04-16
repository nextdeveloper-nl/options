<?php

namespace NextDeveloper\Options\Http\Requests\Requests;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class RequestsCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'uri' => 'required|string',
        'method' => 'required|string',
        'controller' => 'required|string',
        'topic' => 'nullable|string',
        'controller_description' => 'nullable|string',
        'action' => 'required',
        'action_description' => 'nullable|string',
        'middleware' => 'nullable',
        'search_filters' => 'nullable',
        'requests' => 'nullable',
        'returns' => 'nullable',
        'linked_objects' => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE\n\n\n\n\n\n\n\n\n\n\n\n
}