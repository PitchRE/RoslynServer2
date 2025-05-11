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
 * @property int $site_id
 * @property int $device_id
 * @property int|null $partition_id
 * @property string $name
 * @property string $zone_number
 * @property string|null $zone_type
 * @property string|null $physical_location_description
 * @property string|null $sensor_type
 * @property bool $is_bypassed
 * @property bool $is_enabled
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Device $device
 * @property-read \App\Models\Partition|null $partition
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SecurityEvent> $securityEvents
 * @property-read int|null $security_events_count
 * @property-read \App\Models\Site|null $site
 * @method static \Database\Factories\ZoneFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereIsBypassed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereIsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone wherePartitionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone wherePhysicalLocationDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereSensorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereSiteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereZoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereZoneType($value)
 * @mixin \Eloquent
 */
class Zone extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'device_id',
        'partition_id', // Optional: if zones are strictly assigned to one partition
        'name', // e.g., "Front Door Contact", "Living Room Motion", "Kitchen Smoke"
        'zone_number', // The number used by the panel (e.g., "001", "05", "101")
        'zone_type', // e.g., 'ENTRY_EXIT', 'PERIMETER', 'INTERIOR_FOLLOWER', 'FIRE', '24_HOUR_SILENT' (Consider Enum)
        'physical_location_description', // More descriptive location, e.g., "Window above sink"
        'is_bypassed', // Current bypass status
        'is_enabled', // If this zone is actively used/monitored
        'sensor_type', // e.g., 'Magnetic Contact', 'PIR Motion', 'Photoelectric Smoke' (Consider Enum)
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_bypassed' => 'boolean',
        'is_enabled' => 'boolean',
        // 'zone_type' => ZoneTypeEnum::class,
        // 'sensor_type' => SensorTypeEnum::class,
    ];

    /**
     * The site this partition belongs to.
     */
    public function site(): HasOneThrough
    {

        return $this->hasOneThrough(Site::class, Device::class);
    }

    /**
     * The device (panel) this zone is connected to.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    /**
     * The partition this zone belongs to (if applicable).
     */
    public function partition(): BelongsTo
    {
        return $this->belongsTo(Partition::class, 'partition_id'); // A zone might not always be in a partition or could be global
    }

    /**
     * Security events specifically attributed to this zone.
     */
    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class, 'zone_id');
    }
}
