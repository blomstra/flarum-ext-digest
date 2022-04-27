<?php

use Flarum\Database\Migration;

return Migration::addColumns('users', [
    // We need to use a column instead of a user setting because the scheduled command needs to retrieve users with a specific value
    'digest_frequency' => ['string', 'length' => 50, 'nullable' => true, 'index' => true],
]);
