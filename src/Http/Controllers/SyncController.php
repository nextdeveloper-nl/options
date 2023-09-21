<?php

namespace NextDeveloper\Options\Http\Controllers;

use NextDeveloper\Options\Services\OptionsService;

class SyncController
{
    public function sync() {
        //  Gets the name of the module to sync
        OptionsService::generate([
            'NextDeveloper'
        ]);
    }
}