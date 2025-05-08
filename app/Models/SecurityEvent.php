<?php

namespace App\Models; // Or your chosen namespace for models

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne; // Or HasMany for RawEventLog
use App\Enums\EventCategory;
use App\Enums\EventQualifier;
use App\Enums\EventType;
// Assuming you might have an Operator model and Site/CustomerAccount model
// use App\Models\Operator;
// use App\Models\Site;
// use App\Models\Device;
// use App\Models\Partition;
// use App\Models\Zone;
// use App\Models\User; // End-user related to the event
// use App\Models\ResolutionCode;

class SecurityEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'occurred_at',
        'received_at',
        'processed_at',
        'external_event_id',
        'source_protocol', // Consider making this an Enum too if finite set
        'raw_event_code',
        'raw_event_description',
        'site_id',
        'raw_account_identifier',
        'device_id',
        'raw_device_identifier',
        'partition_id',
        'raw_partition_identifier',
        'zone_id',
        'raw_zone_identifier',
        'user_id', // This is the end-user/keyfob holder, not CSR operator
        'raw_user_identifier',
        'event_category',
        'event_type',
        'event_qualifier',
        // 'is_restoral', // We decided to use event_qualifier instead
        'priority', // Consider making this an Enum or a defined integer set
        'normalized_description',
        'message_details',
        'status', // Consider making this an Enum (e.g., EventStatus::NEW)
        'acknowledged_at',
        'acknowledged_by_operator_id',
        'resolved_at',
        'resolved_by_operator_id',
        'resolution_code_id',
        'resolution_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'occurred_at' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'event_category' => EventCategory::class,
        'event_type' => EventType::class,
        'event_qualifier' => EventQualifier::class,
        // 'is_restoral' => 'boolean', // Not used if event_qualifier is primary
        'priority' => 'integer', // Or cast to a PriorityEnum if you create one
        // 'status' => EventStatus::class, // If you create an EventStatus enum
    ];

    /**
     * Get the raw event log associated with this security event.
     * Assuming a one-to-one or one-to-many relationship.
     * If one SecurityEvent can be formed from multiple raw packets, use hasMany.
     */
    // public function rawEventLog(): HasOne // Or HasMany
    // {
    //     return $this->hasOne(RawEventLog::class); // Or $this->hasMany(RawEventLog::class);
    // }

    /**
     * Get the site or customer account associated with this event.
     */
    public function site(): BelongsTo
    {
        // return $this->belongsTo(Site::class, 'site_id');
        // Or CustomerAccount::class
        return $this->belongsTo(Model::class, 'site_id'); // Placeholder
    }

    /**
     * Get the device (e.g., alarm panel) associated with this event.
     */
    public function device(): BelongsTo
    {
        // return $this->belongsTo(Device::class, 'device_id');
        return $this->belongsTo(Model::class, 'device_id'); // Placeholder
    }

    /**
     * Get the partition associated with this event.
     */
    public function partition(): BelongsTo
    {
        // return $this->belongsTo(Partition::class, 'partition_id');
        return $this->belongsTo(Model::class, 'partition_id'); // Placeholder
    }

    /**
     * Get the zone associated with this event.
     */
    public function zone(): BelongsTo
    {
        // return $this->belongsTo(Zone::class, 'zone_id');
        return $this->belongsTo(Model::class, 'zone_id'); // Placeholder
    }

    /**
     * Get the end-user (panel user/keyfob holder) associated with this event.
     */
    public function panelUser(): BelongsTo // Renamed from user() to avoid conflict if using Laravel Auth user
    {
        // return $this->belongsTo(User::class, 'user_id'); // Assuming 'User' is your panel user model
        return $this->belongsTo(Model::class, 'user_id'); // Placeholder
    }

    /**
     * Get the CSR operator who acknowledged this event.
     */
    public function acknowledgedByOperator(): BelongsTo
    {
        // return $this->belongsTo(Operator::class, 'acknowledged_by_operator_id');
        return $this->belongsTo(Model::class, 'acknowledged_by_operator_id'); // Placeholder
    }

    /**
     * Get the CSR operator who resolved this event.
     */
    public function resolvedByOperator(): BelongsTo
    {
        // return $this->belongsTo(Operator::class, 'resolved_by_operator_id');
        return $this->belongsTo(Model::class, 'resolved_by_operator_id'); // Placeholder
    }

    /**
     * Get the resolution code for this event.
     */
    public function resolutionCode(): BelongsTo
    {
        // return $this->belongsTo(ResolutionCode::class, 'resolution_code_id');
        return $this->belongsTo(Model::class, 'resolution_code_id'); // Placeholder
    }

    // --- Accessors & Mutators (Optional but useful) ---

    /**
     * Get a combined account identifier, preferring resolved site name or raw identifier.
     * Example:
     * public function getDisplayAccountIdentifierAttribute(): string
     * {
     *     return $this->site?->name ?? $this->raw_account_identifier ?? 'N/A';
     * }
     */

    /**
     * Determine if the event is considered an "active alarm" needing immediate attention.
     * This would be based on combinations of category, type, and qualifier.
     * Example:
     * public function isActiveAlarm(): bool
     * {
     *     if ($this->event_qualifier === EventQualifier::RESTORAL) {
     *         return false;
     *     }
     *
     *     return in_array($this->event_category, [
     *         EventCategory::ALARM_BURGLARY,
     *         EventCategory::ALARM_PANIC,
     *         EventCategory::ALARM_FIRE,
     *         EventCategory::ALARM_MEDICAL,
     *         // ... other critical categories
     *     ]);
     * }
     */

    // --- Scopes (Optional but useful for querying) ---

    /**
     * Scope a query to only include active (non-restoral, non-acknowledged/resolved) critical alarms.
     * Example:
     * public function scopeActiveCriticalAlarms($query)
     * {
     *     return $query->where('status', EventStatus::NEW) // Assuming EventStatus enum
     *                  ->where('event_qualifier', '!=', EventQualifier::RESTORAL)
     *                  ->whereIn('event_category', [
     *                      EventCategory::ALARM_BURGLARY,
     *                      EventCategory::ALARM_FIRE,
     *                      // ...
     *                  ])
     *                  ->whereIn('priority', [5, 4]); // Assuming priority 4 & 5 are critical
     * }
     */
}