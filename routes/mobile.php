<?php

use App\NativeComponents\Home;
use App\NativeComponents\Settings;
use Illuminate\Support\Facades\Route;

Route::native('/', Home::class);
Route::native('/settings', Settings::class);
