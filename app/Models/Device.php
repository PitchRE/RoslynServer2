<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes; // Optional: if you want soft deletes

/**
 * 
 *
 * @property int $id
 * @property int $site_id
 * @property string|null $name
 * @property string|null $identifier
 * @property string|null $model_name
 * @property string|null $manufacturer
 * @property string|null $firmware_version
 * @property string|null $ip_address
 * @property int|null $port
 * @property string|null $communication_protocol
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $installation_date
 * @property \Illuminate\Support\Carbon|null $last_communication_at
 * @property string|null $notes
 * @property array<array-key, mixed>|null $configuration_details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Partition> $partitions
 * @property-read int|null $partitions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SecurityEvent> $securityEvents
 * @property-read int|null $security_events_count
 * @property-read \App\Models\Site $site
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Zone> $zones
 * @property-read int|null $zones_count
 * @method static \Database\Factories\DeviceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereCommunicationProtocol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereConfigurationDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereFirmwareVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereInstallationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereLastCommunicationAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereManufacturer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device wherePort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereSiteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Device extends Model
{
    use HasFactory;
    // use SoftDeletes; // Uncomment if you want to use soft deletes

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'name', // e.g., "Main Panel - Shop Floor", "Honeywell Vista 20P - Office"
        'identifier', // A unique identifier for the panel (e.g., MAC address, serial number, receiver line if applicable)
        'model_name', // e.g., "Vista 20P", "DSC PowerSeries Neo"
        'manufacturer', // e.g., "Honeywell", "DSC", "Bosch"
        'firmware_version',
        'ip_address', // If IP based reporting
        'port',       // If IP based reporting
        'communication_protocol', // e.g., CONTACT_ID_IP, SIA_DC09_UDP, etc. (Consider an Enum)
        'is_active', // To enable/disable monitoring for this device
        'installation_date',
        'last_communication_at', // Timestamp of the last successful communication
        'notes', // Any relevant notes about the device
        'configuration_details', // JSON column for specific panel config (e.g., zone types, specific mappings)
        // Add any other relevant fields like 'phone_number' if dial-up, 'sim_card_number' for cellular etc.
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'installation_date' => 'date',
        'last_communication_at' => 'datetime',
        'configuration_details' => 'array', // Casts JSON to/from array
        'port' => 'integer',
    ];

    /**
     * The site to which this device belongs.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * The security events generated by this device.
     */
    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class, 'device_id');
    }

    /**
     * The zones associated with this device.
     * (A panel typically has many zones)
     */
    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'device_id');
    }

    /**
     * The partitions associated with this device.
     * (A panel typically has many partitions)
     */
    public function partitions(): HasMany
    {
        return $this->hasMany(Partition::class, 'device_id');
    }

    // You might also have relationships to Users (panel users programmed into this device)
    // or specific configuration profiles.
}
