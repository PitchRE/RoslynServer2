<?php

use App\Models\Device;
use App\Models\SiaDc09Message;
use App\Models\Site;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {

    // return SiaDc09Message::first()->toArray();

    // $site = Site::create([
    //     'name' => 'Biedronka'
    // ]);

    // $device = new Device([
    //     'identifier' => '3333',
    //     'model_nane' => 'Satel Perfecta'
    // ]);

    // $device->site()->associate($site);

    // $device->save();
});
