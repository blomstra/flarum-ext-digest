<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addColumns('digest_queued_blueprints', [
    // Intentionally no foreign key, this isn't the same as the batch ID from Laravel
    'batch' => ['string', 'length' => 255, 'nullable' => true, 'index' => true],
]);
