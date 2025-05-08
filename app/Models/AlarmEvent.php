<?php

declare(strict_types=1);

namespace App\Models;

// Use your specific SiaDc09Message model if creating a relationship
// Enums for standardized fields
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// NEW Enum to be created (can map from others like Contact ID's)

class AlarmEvent extends Model
{
    use HasFactory;
}
