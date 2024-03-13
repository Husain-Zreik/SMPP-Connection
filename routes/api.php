<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $receiver = new \App\Services\Smpp\SmppReceiver();
    $receiver->start();
});
