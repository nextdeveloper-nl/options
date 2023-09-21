<?php

namespace NextDeveloper\Options\Events\Requests;

use Illuminate\Queue\SerializesModels;
use NextDeveloper\Options\Database\Models\Requests;

/**
 * Class RequestsRestoredEvent
 *
 * @package NextDeveloper\Options\Events
 */
class RequestsRestoredEvent
{
    use SerializesModels;

    /**
     * @var Requests
     */
    public $_model;

    /**
     * @var int|null
     */
    protected $timestamp = null;

    public function __construct(Requests $model = null)
    {
        $this->_model = $model;
    }

    /**
     * @param int $value
     *
     * @return AbstractEvent
     */
    public function setTimestamp($value)
    {
        $this->timestamp = $value;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}