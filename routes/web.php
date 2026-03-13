<?php

use App\Http\Controllers\DashboardChartReportController;
use App\Http\Controllers\TopActiveUsersReportController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware('auth')
    ->group(function (): void {
        Route::get('/reports/dashboard/{report}/print', [DashboardChartReportController::class, 'print'])
            ->name('reports.dashboard.print');

        Route::get('/reports/dashboard/{report}/pdf', [DashboardChartReportController::class, 'pdf'])
            ->name('reports.dashboard.pdf');

        Route::get('/reports/dashboard/{report}/excel', [DashboardChartReportController::class, 'excel'])
            ->name('reports.dashboard.excel');

        Route::get('/reports/top-active-users/print', [TopActiveUsersReportController::class, 'print'])
            ->name('reports.top-active-users.print');

        Route::get('/reports/top-active-users/pdf', [TopActiveUsersReportController::class, 'pdf'])
            ->name('reports.top-active-users.pdf');

        Route::get('/reports/top-active-users/excel', [TopActiveUsersReportController::class, 'excel'])
            ->name('reports.top-active-users.excel');
    });
