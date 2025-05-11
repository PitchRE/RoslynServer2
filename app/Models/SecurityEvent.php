<?php

namespace App\Models;

use App\Enums\SecurityEventCategory;
use App\Enums\SecurityEventQualifier;
use App\Enums\SecurityEventStatus;
use App\Enums\SecurityEventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo; // For date types

// --- Assuming these models exist ---
// use App\Models\Site;
// use App\Models\Device;
// use App\Models\Partition;
// use App\Models\Zone;
// For panelUser and Operator
// use App\Models\ResolutionCode;
// use App\Models\RawEventLog;

/**
 * App\Models\SecurityEvent
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $occurred_at
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property string|null $external_event_id
 * @property string|null $source_protocol
 * @property string|null $raw_event_code
 * @property string|null $raw_event_description
 * @property int|null $device_id
 * @property string|null $raw_device_identifier
 * @property string|null $raw_account_identifier
 * @property int|null $panel_user_id
 * @property string|null $raw_panel_user_identifier
 * @property int|null $partition_id
 * @property string|null $raw_partition_identifier
 * @property int|null $zone_id
 * @property string|null $raw_zone_identifier
 * @property SecurityEventCategory|null $event_category
 * @property SecurityEventType|null $event_type
 * @property SecurityEventQualifier|null $event_qualifier
 * @property int|null $priority
 * @property string|null $normalized_description
 * @property string|null $message_details
 * @property SecurityEventStatus $status
 * @property \Illuminate\Support\Carbon|null $acknowledged_at
 * @property int|null $acknowledged_by_operator_id
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property int|null $resolved_by_operator_id
 * @property int|null $resolution_code_id
 * @property string|null $resolution_notes
 * @property string|null $source_message_type
 * @property int|null $source_message_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $acknowledgedByOperator
 * @property-read \App\Models\Device|null $device
 * @property-read string $display_account_identifier
 * @property-read string $display_panel_user
 * @property-read string $display_zone_identifier
 * @property-read \App\Models\User|null $panelUser
 * @property-read \App\Models\Partition|null $partition
 * @property-read \App\Models\ResolutionCode|null $resolutionCode
 * @property-read \App\Models\User|null $resolvedByOperator
 * @property-read \App\Models\Site|null $site
 * @property-read Model|\Eloquent|null $sourceMessage
 * @property-read \App\Models\Zone|null $zone
 *
 * @method static Builder<static>|SecurityEvent activeAlarms()
 * @method static \Database\Factories\SecurityEventFactory factory($count = null, $state = [])
 * @method static Builder<static>|SecurityEvent newModelQuery()
 * @method static Builder<static>|SecurityEvent newQuery()
 * @method static Builder<static>|SecurityEvent query()
 * @method static Builder<static>|SecurityEvent requiresOperatorResolution()
 * @method static Builder<static>|SecurityEvent whereAcknowledgedAt($value)
 * @method static Builder<static>|SecurityEvent whereAcknowledgedByOperatorId($value)
 * @method static Builder<static>|SecurityEvent whereCreatedAt($value)
 * @method static Builder<static>|SecurityEvent whereDeviceId($value)
 * @method static Builder<static>|SecurityEvent whereEventCategory($value)
 * @method static Builder<static>|SecurityEvent whereEventQualifier($value)
 * @method static Builder<static>|SecurityEvent whereEventType($value)
 * @method static Builder<static>|SecurityEvent whereExternalEventId($value)
 * @method static Builder<static>|SecurityEvent whereId($value)
 * @method static Builder<static>|SecurityEvent whereMessageDetails($value)
 * @method static Builder<static>|SecurityEvent whereNormalizedDescription($value)
 * @method static Builder<static>|SecurityEvent whereOccurredAt($value)
 * @method static Builder<static>|SecurityEvent wherePanelUserId($value)
 * @method static Builder<static>|SecurityEvent wherePartitionId($value)
 * @method static Builder<static>|SecurityEvent wherePriority($value)
 * @method static Builder<static>|SecurityEvent whereProcessedAt($value)
 * @method static Builder<static>|SecurityEvent whereRawAccountIdentifier($value)
 * @method static Builder<static>|SecurityEvent whereRawDeviceIdentifier($value)
 * @method static Builder<static>|SecurityEvent whereRawEventCode($value)
 * @method static Builder<static>|SecurityEvent whereRawEventDescription($value)
 * @method static Builder<static>|SecurityEvent whereRawPanelUserIdentifier($value)
 * @method static Builder<static>|SecurityEvent whereRawPartitionIdentifier($value)
 * @method static Builder<static>|SecurityEvent whereRawZoneIdentifier($value)
 * @method static Builder<static>|SecurityEvent whereResolutionCodeId($value)
 * @method static Builder<static>|SecurityEvent whereResolutionNotes($value)
 * @method static Builder<static>|SecurityEvent whereResolvedAt($value)
 * @method static Builder<static>|SecurityEvent whereResolvedByOperatorId($value)
 * @method static Builder<static>|SecurityEvent whereSourceMessageId($value)
 * @method static Builder<static>|SecurityEvent whereSourceMessageType($value)
 * @method static Builder<static>|SecurityEvent whereSourceProtocol($value)
 * @method static Builder<static>|SecurityEvent whereStatus($value)
 * @method static Builder<static>|SecurityEvent whereUpdatedAt($value)
 * @method static Builder<static>|SecurityEvent whereZoneId($value)
 *
 * @mixin \Eloquent
 */
class SecurityEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'occurred_at',
        'received_at',
        'processed_at',
        'external_event_id',
        'source_protocol',
        'raw_event_code',
        'raw_event_description',
        'site_id',
        'raw_account_identifier',
        'raw_account_identifier',
        'device_id',
        'raw_device_identifier',
        'partition_id',
        'raw_partition_identifier',
        'zone_id',
        'raw_zone_identifier',
        'user_id', // Panel User ID
        'raw_user_identifier',
        'event_category',
        'event_type',
        'event_qualifier',
        'priority',
        'normalized_description',
        'message_details',
        'status',
        'acknowledged_at',
        'acknowledged_by_operator_id',
        'resolved_at',
        'resolved_by_operator_id',
        'resolution_code_id',
        'resolution_notes',
        'source_message_id',
        'source_message_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'event_category' => SecurityEventCategory::class,
        'event_type' => SecurityEventType::class,
        'event_qualifier' => SecurityEventQualifier::class,
        'status' => SecurityEventStatus::class,
        'priority' => 'integer',
    ];

    /**
     * Default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => SecurityEventStatus::NEW,
    ];

    // --- RELATIONSHIPS ---
    // (Keep relationships as they were, they are generally correct)

    public function sourceMessage(): MorphTo
    {
        return $this->morphTo();
    }

    public function site(): HasOneThrough
    {
        return $this->hasOneThrough(
            Site::class,   // Final model
            Device::class, // Intermediate model
            'id',          // Foreign key on Device table...
            'id',          // Foreign key on Site table...
            'device_id',   // Local key on current model (ModelA)...
            'site_id'      // Local key on Device table...
        );
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function partition(): BelongsTo
    {
        return $this->belongsTo(Partition::class, 'partition_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function panelUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function acknowledgedByOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_operator_id');
    }

    public function resolvedByOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_operator_id');
    }

    public function resolutionCode(): BelongsTo
    {
        return $this->belongsTo(ResolutionCode::class, 'resolution_code_id');
    }

    // --- SCOPES ---
    // (Scopes should be fine as they query the builder, not directly access $this->property)
    // Make sure all enum constants used in scopes are correct as per your enum files.

    public function scopeRequiresOperatorResolution(Builder $query): Builder
    {
        // Ensure SecurityEventStatus::getOpenWorkflowStatuses() exists and is static
        $openStatuses = array_map(fn ($status) => $status->value, SecurityEventStatus::getOpenWorkflowStatuses());

        return $query->whereIn('status', $openStatuses)
            ->where(function (Builder $q) {
                $q->whereIn('event_category', [
                    SecurityEventCategory::ALARM_BURGLARY,
                    SecurityEventCategory::ALARM_PANIC_DURESS,
                    SecurityEventCategory::ALARM_FIRE,
                    SecurityEventCategory::ALARM_MEDICAL,
                    SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD,
                ])
                    ->orWhere(function (Builder $subQ) {
                        $criticalPriorities = [5, 4];
                        $subQ->whereIn('priority', $criticalPriorities);
                    })
                    ->orWhere(function (Builder $subQ) {
                        $subQ->where('event_category', SecurityEventCategory::SUPERVISORY_CLIENT_SYSTEM)
                            ->where('event_type', SecurityEventType::SUPERVISORY_FAILURE_TO_ARM_SCHEDULE); // Corrected to match enum
                    });
            })
            ->whereNot(function (Builder $qNot) {
                $qNot->where('event_qualifier', SecurityEventQualifier::RESTORAL)
                    ->whereIn('event_category', [
                        SecurityEventCategory::SYSTEM_TROUBLE_NON_CRITICAL,
                    ])
                    ->where('priority', '<=', 2);
            })
            ->orderByRaw('FIELD(priority, 5, 4, 3, 2, 1) DESC NULLS LAST')
            ->orderBy('occurred_at', 'asc');
    }

    public function scopeActiveAlarms(Builder $query): Builder
    {
        return $query->where('event_qualifier', '!=', SecurityEventQualifier::RESTORAL)
            ->whereIn('event_category', [
                SecurityEventCategory::ALARM_BURGLARY,
                SecurityEventCategory::ALARM_PANIC_DURESS,
                SecurityEventCategory::ALARM_FIRE,
                SecurityEventCategory::ALARM_MEDICAL,
                SecurityEventCategory::ALARM_ENVIRONMENTAL_HAZARD,
                SecurityEventCategory::ALARM_SYSTEM_TAMPER,
            ]);
    }

    // --- ACCESSORS & MUTATORS ---
    // (Accessors should be fine as they use $this->attribute which Eloquent handles)

    public function getDisplayAccountIdentifierAttribute(): string
    {
        return $this->site->name ?? $this->raw_account_identifier ?? 'N/A';
    }

    public function getDisplayZoneIdentifierAttribute(): string
    {
        return $this->zone->name ?? $this->raw_zone_identifier ?? 'N/A';
    }

    public function getDisplayPanelUserAttribute(): string
    {
        return $this->panelUser->name ?? $this->raw_user_identifier ?? 'N/A';
    }

    // --- BUSINESS LOGIC METHODS ---

    public function isOpen(): bool
    {
        // This access is now fine due to property declaration
        return in_array($this->status, SecurityEventStatus::getOpenWorkflowStatuses());
    }

    public function isRestoral(): bool
    {
        // This access is now fine due to property declaration
        return $this->event_qualifier === SecurityEventQualifier::RESTORAL;
    }

    public function acknowledge(User $operator): self
    {
        if ($this->isOpen()) {
            // These accesses are now fine
            $this->status = SecurityEventStatus::ACKNOWLEDGED;
            $this->acknowledged_by_operator_id = $operator->id;
            $this->acknowledged_at = now();
            $this->save();
        }

        return $this;
    }

    public function resolve(User $operator, ResolutionCode $resolutionCode, ?string $notes = null): self
    {
        // Ensure SecurityEventStatus::RESOLVED exists in your enum
        // If not, use an appropriate existing "closed" status.
        $this->status = SecurityEventStatus::CLOSED_CONFIRMED_ACTUAL; // Verify this case exists
        $this->resolved_by_operator_id = $operator->id;
        $this->resolution_code_id = $resolutionCode->id;
        $this->resolution_notes = $notes;
        $this->resolved_at = now();
        $this->save();

        return $this;
    }
}
