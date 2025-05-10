<?php

namespace App\Models;

use App\Enums\SecurityEventCategory;
use App\Enums\SecurityEventQualifier;
use App\Enums\SecurityEventStatus;
use App\Enums\SecurityEventType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property Carbon|null $occurred_at
 * @property Carbon|null $received_at
 * @property Carbon|null $processed_at
 * @property string|null $external_event_id
 * @property string|null $source_protocol
 * @property string|null $raw_event_code
 * @property string|null $raw_event_description
 * @property int|null $site_id
 * @property string|null $raw_account_identifier
 * @property int|null $device_id
 * @property string|null $raw_device_identifier
 * @property int|null $partition_id
 * @property string|null $raw_partition_identifier
 * @property int|null $zone_id
 * @property string|null $raw_zone_identifier
 * @property int|null $user_id Panel User ID
 * @property string|null $raw_user_identifier
 * @property SecurityEventCategory|null $event_category
 * @property SecurityEventType|null $event_type
 * @property SecurityEventQualifier|null $event_qualifier
 * @property int|null $priority
 * @property string|null $normalized_description
 * @property string|null $message_details
 * @property SecurityEventStatus $status
 * @property Carbon|null $acknowledged_at
 * @property int|null $acknowledged_by_operator_id
 * @property Carbon|null $resolved_at
 * @property int|null $resolved_by_operator_id
 * @property int|null $resolution_code_id
 * @property string|null $resolution_notes
 * @property int|null $source_message_id
 * @property string|null $source_message_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Site|null $site
 * @property-read Device|null $device
 * @property-read Partition|null $partition
 * @property-read Zone|null $zone
 * @property-read User|null $panelUser
 * @property-read User|null $acknowledgedByOperator
 * @property-read User|null $resolvedByOperator
 * @property-read ResolutionCode|null $resolutionCode
 * @property-read Model|\Eloquent $sourceMessage
 * // Add other relationships if RawEventLog is used
 */
class SecurityEvent extends Model
{
    use HasFactory;

    // --- Property declarations for PHPStan ---
    public ?Carbon $occurred_at;

    public ?Carbon $received_at;

    public ?Carbon $processed_at;

    public ?string $external_event_id;

    public ?string $source_protocol;

    public ?string $raw_event_code;

    public ?string $raw_event_description;

    public ?int $site_id;

    public ?string $raw_account_identifier;

    public ?int $device_id;

    public ?string $raw_device_identifier;

    public ?int $partition_id;

    public ?string $raw_partition_identifier;

    public ?int $zone_id;

    public ?string $raw_zone_identifier;

    public ?int $user_id; // Panel User ID

    public ?string $raw_user_identifier;

    public ?SecurityEventCategory $event_category;

    public ?SecurityEventType $event_type;

    public ?SecurityEventQualifier $event_qualifier;

    public ?int $priority;

    public ?string $normalized_description;

    public ?string $message_details;

    public SecurityEventStatus $status; // Not nullable due to default

    public ?Carbon $acknowledged_at;

    public ?int $acknowledged_by_operator_id;

    public ?Carbon $resolved_at;

    public ?int $resolved_by_operator_id;

    public ?int $resolution_code_id;

    public ?string $resolution_notes;

    public ?int $source_message_id;

    public ?string $source_message_type;

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
        'received_at' => 'datetime',
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

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
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
