<?php

namespace Blomstra\Digest;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $blueprint
 * @property Carbon $date
 *
 * @property User $user
 */
class QueuedBlueprint extends AbstractModel
{
    protected $table = 'digest_queued_blueprints';

    protected $casts = [
        'date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
