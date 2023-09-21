<?php

namespace NextDeveloper\Options\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\Options\Database\Models\Requests;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\Options\Http\Transformers\AbstractTransformers\AbstractRequestsTransformer;

/**
 * Class RequestsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\Options\Http\Transformers
 */
class RequestsTransformer extends AbstractRequestsTransformer
{

    /**
     * @param Requests $model
     *
     * @return array
     */
    public function transform(Requests $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('Requests', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('Requests', $model->uuid, 'Transformed'),
            $transformed
        );

        return parent::transform($model);
    }
}
