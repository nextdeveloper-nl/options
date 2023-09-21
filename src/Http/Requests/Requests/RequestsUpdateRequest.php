<?php

namespace NextDeveloper\Options\Http\Requests\Requests;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class RequestsUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'uri'                    => 'nullable|string|max:500',
        'method'                 => 'nullable|string|max:500',
        'controller'             => 'nullable|string|max:500',
        'topic'                  => 'nullable|string|max:500',
        'controller_description' => 'nullable|string',
        'action'                 => 'nullable|string|max:500',
        'action_description'     => 'nullable|string',
        'middleware'             => 'nullable|string',
        'search_filters'         => 'nullable|string',
        'requests'               => 'nullable|string',
        'returns'                => 'nullable|string',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}