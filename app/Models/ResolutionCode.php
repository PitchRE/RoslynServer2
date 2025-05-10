<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
