<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2023 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addColumns('users', [
    'digest_hour' => ['integer', 'nullable' => true],
]);
