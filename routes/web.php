<?php

use App\Models\Device;
use App\Models\SiaDc09Message;
use App\Models\Site;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {

    //   return SiaDc09Message::first()->toArray();

    // $site = Site::create([
    //     'name' => 'Biedronka'
    // ]);

    // $device = new Device([
    //     'identifier' => '0499',
    //     'model_nane' => 'radio'
    // ]);

    // $device->site()->associate($site);

    // $device->save();
});
