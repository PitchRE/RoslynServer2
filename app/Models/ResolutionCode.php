<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SecurityEvent> $securityEvents
 * @property-read int|null $security_events_count
 *
 * @method static \Database\Factories\ResolutionCodeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResolutionCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResolutionCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResolutionCode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResolutionCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResolutionCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ResolutionCode whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ResolutionCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'category', // e.g., FALSE_ALARM, ACTUAL_EVENT, DISPATCH, TEST (could be an enum)
        'requires_dispatch',
        'is_false_alarm',
        // Add other relevant attributes like 'is_billable', 'default_priority_override' etc.
    ];

    protected $casts = [
        'requires_dispatch' => 'boolean',
        'is_false_alarm' => 'boolean',
        // 'category' => ResolutionCodeCategoryEnum::class, // If you make category an enum
    ];

    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class, 'resolution_code_id');
    }
}
