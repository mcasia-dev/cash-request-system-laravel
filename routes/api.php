<?php

use App\Http\Controllers\Api\PhilippineHolidayController;
use Illuminate\Support\Facades\Route;

Route::get('/holidays/philippines/check', PhilippineHolidayController::class);
