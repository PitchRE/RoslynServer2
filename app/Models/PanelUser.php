<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * @property int $id
 * @property string $name
 * @property string $notes
 * @property int $device_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Device $device
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SecurityEvent> $securityEvents
 * @property-read int|null $security_events_count
 * @property-read \App\Models\Site|null $site
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PanelUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PanelUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PanelUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PanelUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PanelUser whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PanelUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PanelUser whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PanelUser whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PanelUser whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PanelUser extends Model
{
    /**
     * Security events specifically attributed to this zone.
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'device_id',
        'name', // e.g., "Front Door Contact", "Living Room Motion", "Kitchen Smoke"
        'notes',
    ];

    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class, 'user_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function site(): HasOneThrough
    {

        return $this->hasOneThrough(Site::class, Device::class);
    }
}
