<?php

namespace NextDeveloper\Options\Tests\Database\Models;

use Tests\TestCase;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use NextDeveloper\Options\Database\Filters\OptionRequestQueryFilter;
use NextDeveloper\Options\Services\AbstractServices\AbstractOptionRequestService;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Resource\Collection;

trait OptionRequestTestTraits
{
    public $http;

    /**
     *   Creating the Guzzle object
     */
    public function setupGuzzle()
    {
        $this->http = new Client(
            [
            'base_uri'  =>  '127.0.0.1:8000'
            ]
        );
    }

    /**
     *   Destroying the Guzzle object
     */
    public function destroyGuzzle()
    {
        $this->http = null;
    }

    public function test_http_optionrequest_get()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'GET',
            '/options/optionrequest',
            ['http_errors' => false]
        );

        $this->assertContains(
            $response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND
            ]
        );
    }

    public function test_http_optionrequest_post()
    {
        $this->setupGuzzle();
        $response = $this->http->request(
            'POST', '/options/optionrequest', [
            'form_params'   =>  [
                'uri'  =>  'a',
                'method'  =>  'a',
                'controller'  =>  'a',
                'topic'  =>  'a',
                'controller_description'  =>  'a',
                'action_description'  =>  'a',
                            ],
                ['http_errors' => false]
            ]
        );

        $this->assertEquals($response->getStatusCode(), Response::HTTP_OK);
    }

    /**
     * Get test
     *
     * @return bool
     */
    public function test_optionrequest_model_get()
    {
        $result = AbstractOptionRequestService::get();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_optionrequest_get_all()
    {
        $result = AbstractOptionRequestService::getAll();

        $this->assertIsObject($result, Collection::class);
    }

    public function test_optionrequest_get_paginated()
    {
        $result = AbstractOptionRequestService::get(
            null, [
            'paginated' =>  'true'
            ]
        );

        $this->assertIsObject($result, LengthAwarePaginator::class);
    }

    public function test_optionrequest_event_retrieved_without_object()
    {
        try {
            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestRetrievedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_created_without_object()
    {
        try {
            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestCreatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_creating_without_object()
    {
        try {
            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestCreatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_saving_without_object()
    {
        try {
            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestSavingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_saved_without_object()
    {
        try {
            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestSavedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_updating_without_object()
    {
        try {
            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestUpdatingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_updated_without_object()
    {
        try {
            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestUpdatedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_deleting_without_object()
    {
        try {
            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestDeletingEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_deleted_without_object()
    {
        try {
            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestDeletedEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_restoring_without_object()
    {
        try {
            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestRestoringEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_restored_without_object()
    {
        try {
            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestRestoredEvent());
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_retrieved_with_object()
    {
        try {
            $model = \NextDeveloper\Options\Database\Models\OptionRequest::first();

            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestRetrievedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_created_with_object()
    {
        try {
            $model = \NextDeveloper\Options\Database\Models\OptionRequest::first();

            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestCreatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_creating_with_object()
    {
        try {
            $model = \NextDeveloper\Options\Database\Models\OptionRequest::first();

            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestCreatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_saving_with_object()
    {
        try {
            $model = \NextDeveloper\Options\Database\Models\OptionRequest::first();

            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestSavingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_saved_with_object()
    {
        try {
            $model = \NextDeveloper\Options\Database\Models\OptionRequest::first();

            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestSavedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_updating_with_object()
    {
        try {
            $model = \NextDeveloper\Options\Database\Models\OptionRequest::first();

            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestUpdatingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_updated_with_object()
    {
        try {
            $model = \NextDeveloper\Options\Database\Models\OptionRequest::first();

            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestUpdatedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_deleting_with_object()
    {
        try {
            $model = \NextDeveloper\Options\Database\Models\OptionRequest::first();

            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestDeletingEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_deleted_with_object()
    {
        try {
            $model = \NextDeveloper\Options\Database\Models\OptionRequest::first();

            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestDeletedEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_restoring_with_object()
    {
        try {
            $model = \NextDeveloper\Options\Database\Models\OptionRequest::first();

            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestRestoringEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    public function test_optionrequest_event_restored_with_object()
    {
        try {
            $model = \NextDeveloper\Options\Database\Models\OptionRequest::first();

            event(new \NextDeveloper\Options\Events\OptionRequest\OptionRequestRestoredEvent($model));
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_uri_filter()
    {
        try {
            $request = new Request(
                [
                'uri'  =>  'a'
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_method_filter()
    {
        try {
            $request = new Request(
                [
                'method'  =>  'a'
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_controller_filter()
    {
        try {
            $request = new Request(
                [
                'controller'  =>  'a'
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_topic_filter()
    {
        try {
            $request = new Request(
                [
                'topic'  =>  'a'
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_controller_description_filter()
    {
        try {
            $request = new Request(
                [
                'controller_description'  =>  'a'
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_action_description_filter()
    {
        try {
            $request = new Request(
                [
                'action_description'  =>  'a'
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_created_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now()
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_updated_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now()
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_deleted_at_filter_start()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now()
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_created_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_updated_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_deleted_at_filter_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_created_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'created_atStart'  =>  now(),
                'created_atEnd'  =>  now()
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_updated_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'updated_atStart'  =>  now(),
                'updated_atEnd'  =>  now()
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function test_optionrequest_event_deleted_at_filter_start_and_end()
    {
        try {
            $request = new Request(
                [
                'deleted_atStart'  =>  now(),
                'deleted_atEnd'  =>  now()
                ]
            );

            $filter = new OptionRequestQueryFilter($request);

            $model = \NextDeveloper\Options\Database\Models\OptionRequest::filter($filter)->first();
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->assertTrue(true);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n
}