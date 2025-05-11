<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes; // Optional

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string|null $state_province
 * @property string|null $postal_code
 * @property string|null $country
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $primary_contact_name
 * @property string|null $primary_contact_phone
 * @property string|null $primary_contact_email
 * @property string|null $timezone
 * @property bool $is_active
 * @property string|null $monitoring_service_level
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Device> $devices
 * @property-read int|null $devices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SecurityEvent> $securityEvents
 * @property-read int|null $security_events_count
 * @method static \Database\Factories\SiteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereAddressLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereMonitoringServiceLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site wherePrimaryContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site wherePrimaryContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site wherePrimaryContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereStateProvince($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Site extends Model
{
    use HasFactory;
    // use SoftDeletes; // Uncomment for soft deletes

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id', // Optional: If you have a separate Customers table
        'name', // e.g., "Main Street Store", "Johnson Residence"
        'address_line_1',
        'address_line_2',
        'city',
        'state_province', // State or Province
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'primary_contact_name',
        'primary_contact_phone',
        'primary_contact_email',
        'timezone', // e.g., 'America/New_York'
        'is_active', // Is this site actively monitored?
        'monitoring_service_level', // e.g., 'Basic', 'Premium', 'Commercial Fire' (Consider Enum)
        'notes',
        // 'dealer_id' // Optional: If you track dealers/installers
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8', // Example: 8 decimal places for latitude
        'longitude' => 'decimal:8', // Example: 8 decimal places for longitude
    ];

    /**
     * The devices (panels) located at this site.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'site_id');
    }

    /**
     * The security events associated with this site.
     */
    public function securityEvents(): HasManyThrough
    {
        return $this->hasManyThrough(SecurityEvent::class, Device::class);
    }

    /**
     * The panel users associated with this site.
    //  */
    // public function panelUsers(): HasMany
    // {
    //     // Assuming your User model has a site_id for panel users
    //     return $this->hasMany(User::class, 'site_id');
    // }

    /**
     * Optional: Customer who owns this site.
     */
    // public function customer(): BelongsTo
    // {
    //     return $this->belongsTo(Customer::class, 'customer_id');
    // }
}
