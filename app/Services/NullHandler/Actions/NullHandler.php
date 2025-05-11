<?php

// app/Services/AlarmDataFormats/System/Actions/NoOpHandler.php

namespace App\Services\NullHandler\Actions;

use App\Models\SiaDc09Message;
// Use a default qualifier
use Illuminate\Support\Facades\Log;
use App\Contracts\AlarmDataFormats\AlarmDataInterpreter;

class NullHandler // implements AlarmDataInterpreter // Implement if you have the contract
{
    public function handle(SiaDc09Message $originalMessage)
    {

        Log::info(`Transmission Test Received from {$originalMessage->panel_account_number}`);
        return null;
    }
}
