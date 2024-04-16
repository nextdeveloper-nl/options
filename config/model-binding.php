<?php

return [
    'optionrequest' => function ($value) {
        return NextDeveloper\Options\Database\Models\OptionRequest::findByRef($value);
    },

// EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
];