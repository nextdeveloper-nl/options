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
            'uri'                    => 'required|string|max:500',
        'method'                 => 'required|string|max:500',
        'controller'             => 'required|string|max:500',
        'topic'                  => 'nullable|string|max:500',
        'controller_description' => 'nullable|string',
        'action'                 => 'required|string|max:500',
        'action_description'     => 'nullable|string',
        'middleware'             => 'nullable|string',
        'search_filters'         => 'nullable|string',
        'requests'               => 'nullable|string',
        'returns'                => 'nullable|string',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE\n\n
}