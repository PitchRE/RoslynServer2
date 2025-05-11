<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * 
 *
 * @property int $id
 * @property int $device_id
 * @property string $name
 * @property string $partition_number
 * @property string|null $current_status
 * @property bool $is_enabled
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Device $device
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SecurityEvent> $securityEvents
 * @property-read int|null $security_events_count
 * @property-read \App\Models\Site|null $site
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Zone> $zones
 * @property-read int|null $zones_count
 * @method static \Database\Factories\PartitionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition whereCurrentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition whereIsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition wherePartitionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Partition whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Partition extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'device_id', // The specific panel this partition belongs to
        'name', // e.g., "Main Office", "Warehouse Area", "Upstairs"
        'partition_number', // The number used by the panel to identify this partition (e.g., "01", "02", "A")
        'current_status', // e.g., 'ARMED_AWAY', 'ARMED_STAY', 'DISARMED', 'ALARM' (Consider an Enum)
        'is_enabled', // If this partition is actively used/monitored
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        // 'current_status' => PartitionStatusEnum::class, // If you create an enum
    ];

    /**
     * The site this partition belongs to.
     */
    public function site(): HasOneThrough
    {

        return $this->hasOneThrough(Site::class, Device::class);
    }

    /**
     * The device (panel) this partition is part of.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    /**
     * The zones belonging to this partition.
     */
    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'partition_id');
    }

    /**
     * Security events specifically attributed to this zone.
     */
    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class, 'partition_id');
    }
}
