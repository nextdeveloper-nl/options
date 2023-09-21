<?php

namespace NextDeveloper\Options\EventHandlers\RequestsCreatedEvent;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class RequestsCreatedEvent
 *
 * @package PlusClouds\Account\Handlers\Events
 */
class RequestsCreatedEvent implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle($event)
    {

    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}