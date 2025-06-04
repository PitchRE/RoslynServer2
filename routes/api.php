<?php

use App\Services\SiaIpDc09\Actions\HandleMessage as HandleSiaIpMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/siaipdc09', function (Request $request) {

    $response = HandleSiaIpMessage::run($request->raw_message_hex, '127.0.0.1', '7000');

    return [
        'data' => [
            'response_sent' => bin2hex($response),
        ],

    ];

});
