<?php

use Illuminate\Auth\Events\Login;
use App\Listeners\LogLoginListener;

protected $listen = [
    Login::class => [
        LogLoginListener::class,
    ],
];
