<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RepairObservation extends Model
{
    protected $fillable = [
        'role',
        'kind',
        'content',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(RepairSession::class, 'repair_session_id');
    }
}
