<?php

namespace App\Observers;

use App\Models\SiaDc09Message;
use App\Services\AlarmDataFormats\AdemcoContactId\Actions\InterpretAdemcoContactIdData;
use App\Services\SiaIpDc09\Enums\ProcessingStatus;
use App\Services\SiaIpDc09\Enums\SiaToken;

class SiaDc09MessageObserver
{
    /**
     * Handle the SiaDc09Message "created" event.
     */
    public function created(SiaDc09Message $siaDc09Message): void {}

    /**
     * Handle the SiaDc09Message "updated" event.
     */
    public function updated(SiaDc09Message $siaDc09Message): void
    {

        if ($siaDc09Message->processing_status == ProcessingStatus::PARSED && $siaDc09Message->protocol_token == SiaToken::ADM_CID->value) {
            InterpretAdemcoContactIdData::run($siaDc09Message->panel_account_number, $siaDc09Message->message_data, $siaDc09Message->sia_timestamp);
        }
    }

    /**
     * Handle the SiaDc09Message "deleted" event.
     */
    public function deleted(SiaDc09Message $siaDc09Message): void
    {
        //
    }

    /**
     * Handle the SiaDc09Message "restored" event.
     */
    public function restored(SiaDc09Message $siaDc09Message): void
    {
        //
    }

    /**
     * Handle the SiaDc09Message "force deleted" event.
     */
    public function forceDeleted(SiaDc09Message $siaDc09Message): void
    {
        //
    }
}
