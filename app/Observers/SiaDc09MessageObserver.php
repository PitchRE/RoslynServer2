<?php

namespace App\Observers;

use App\Models\SiaDc09Message;
use App\Services\SiaIpDc09\Enums\SiaToken;
use App\Services\SiaIpDc09\Enums\ProcessingStatus;
use App\Services\SiaIpDc09\Actions\RouteSiaMessage;
use App\Services\AlarmDataFormats\AdemcoContactId\Actions\InterpretAdemcoContactIdData;

class SiaDc09MessageObserver
{
    /**
     * Handle the SiaDc09Message "created" event.
     */
    public function created(SiaDc09Message $siaDc09Message): void
    {
    }

    /**
     * Handle the SiaDc09Message "updated" event.
     */
    public function updated(SiaDc09Message $siaDc09Message): void
    {


        RouteSiaMessage::dispatch($siaDc09Message);

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
