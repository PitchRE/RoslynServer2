<?php

use App\Models\SiaDc09Message;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {

    return SiaDc09Message::first()->toArray();
});
