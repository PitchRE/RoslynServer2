<?php

namespace App\Providers;

use App\Models\SiaDc09Message;
use App\Observers\SiaDc09MessageObserver;
use App\Services\SiaIpDc09\Actions\ConfigKeyManagementService;
use App\Services\SiaIpDc09\Actions\DecryptDataBlock;
use App\Services\SiaIpDc09\Actions\EncryptDataBlock;
use App\Services\SiaIpDc09\Contracts\DecryptionService;
use App\Services\SiaIpDc09\Contracts\EncryptionService;
use App\Services\SiaIpDc09\Contracts\KeyManagementService;
use App\Support\Crc\Actions\CalculateCrc16Arc;
use App\Support\Crc\Contracts\CrcCalculator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CrcCalculator::class, CalculateCrc16Arc::class);

        // Bind the KeyManagementService contract to a concrete implementation
        // (e.g., one that reads from the config file)
        //     $this->app->bind(KeyManagementService::class, ConfigKeyManagementService::class);

        // Bind the EncryptionService contract to the EncryptDataBlock action
        // This action itself depends on KeyManagementService, which will be resolved by the container.
        $this->app->bind(EncryptionService::class, EncryptDataBlock::class);

        $this->app->bind(DecryptionService::class, DecryptDataBlock::class);
        $this->app->bind(KeyManagementService::class, ConfigKeyManagementService::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        SiaDc09Message::observe(SiaDc09MessageObserver::class);
    }
}
