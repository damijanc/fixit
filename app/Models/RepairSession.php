<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RepairSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'object_description',
        'symptoms',
        'photo_path',
        'status',
        'summary',
    ];

    public function observations(): HasMany
    {
        return $this->hasMany(RepairObservation::class);
    }

    public function addObservation(string $role, string $content, string $kind = 'message'): RepairObservation
    {
        return $this->observations()->create([
            'role' => $role,
            'kind' => $kind,
            'content' => $content,
        ]);
    }
}
