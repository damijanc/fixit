<?php

use App\NativeComponents\Diagnosis;
use App\NativeComponents\History;
use App\NativeComponents\Home;
use App\NativeComponents\Result;
use App\NativeComponents\Settings;
use Illuminate\Support\Facades\Route;

Route::native('/', Home::class);
Route::native('/diagnosis', Diagnosis::class);
Route::native('/diagnosis/{session}', Diagnosis::class);
Route::native('/result/{session}', Result::class);
Route::native('/history', History::class);
Route::native('/settings', Settings::class);
