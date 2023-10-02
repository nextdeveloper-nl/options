<?php

Route::prefix('options')->group(
    function () {
        Route::prefix('requests')->group(
            function () {
                Route::get('/', 'Requests\RequestsController@index');
                Route::get('/{option_requests}', 'Requests\RequestsController@show');
                Route::post('/', 'Requests\RequestsController@store');
                Route::patch('/{option_requests}', 'Requests\RequestsController@update');
                Route::delete('/{option_requests}', 'Requests\RequestsController@destroy');
            }
        );

        // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n

        Route::get('sync', 'SyncController@sync');
    }
);
