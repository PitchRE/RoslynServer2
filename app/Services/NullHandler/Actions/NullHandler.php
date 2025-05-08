<?php

// app/Services/AlarmDataFormats/System/Actions/NoOpHandler.php

namespace App\Services\NullHandler\Actions;

use App\Contracts\AlarmDataFormats\AlarmDataInterpreter;
// Use a default qualifier
use App\Models\SiaDc09Message;

class NullHandler // implements AlarmDataInterpreter // Implement if you have the contract
{
    public function handle(SiaDc09Message $originalMessage)
    {
        return null;
    }
}
