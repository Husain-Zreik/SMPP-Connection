<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     $receiver = new \App\Services\Smpp\SmppReceiver();
//     $receiver->start();
// });

Route::get('/', function () {
    $transmitter = new \App\Services\Smpp\SmppTransmitter();
    $transmitter->sendSms('Hello from transmitter :)', '123456', '34.89.33.119');
});
