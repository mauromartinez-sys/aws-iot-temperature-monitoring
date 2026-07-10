<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IotDashboardController;
use App\Http\Controllers\IotPointsController;
use App\Http\Controllers\IotAnalyticsController;
use App\Http\Controllers\IotSettingsController;
use App\Http\Controllers\IotUserController;
use App\Http\Controllers\IotOperationController;
use App\Http\Controllers\IotAlarmRuleController;
use App\Http\Controllers\IotAlarmEventController;
use App\Http\Controllers\ScheduledReportController;

Route::get('/', function () {
    return redirect('/iot/dashboard');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/iot/dashboard', [IotDashboardController::class, 'index'])->name('iot.dashboard');

    Route::get('/iot/points', [IotPointsController::class, 'index'])->name('iot.points');

    Route::get('/iot/analytics', [IotAnalyticsController::class, 'index'])->name('iot.analytics');
    Route::get('/iot/analytics/export/csv', [IotAnalyticsController::class, 'exportCsv'])->name('iot.analytics.export.csv');
    Route::get('/iot/analytics/export/excel', [IotAnalyticsController::class, 'exportExcel'])->name('iot.analytics.export.excel');
    Route::get('/iot/analytics/export/pdf', [IotAnalyticsController::class, 'exportPdf'])->name('iot.analytics.export.pdf');

    Route::get('/iot/operations', [IotOperationController::class, 'index'])->name('iot.operations');
    Route::post('/iot/operations', [IotOperationController::class, 'store'])->name('iot.operations.store');

    Route::post('/iot/alarms/{alarmId}/acknowledge', [IotAlarmEventController::class, 'acknowledge'])->name('iot.alarms.acknowledge');
    Route::post('/iot/alarms/{alarmId}/silence', [IotAlarmEventController::class, 'silence'])->name('iot.alarms.silence');
    Route::post('/iot/alarms/{alarmId}/comment', [IotAlarmEventController::class, 'comment'])->name('iot.alarms.comment');

    Route::get('/iot/settings', [IotSettingsController::class, 'index'])->name('iot.settings');
    Route::post('/iot/settings/sensors', [IotSettingsController::class, 'storeSensor'])->name('iot.settings.sensors.store');
    Route::post('/iot/settings/sensors/update', [IotSettingsController::class, 'updateSensors'])->name('iot.settings.sensors.update');

    Route::get('/iot/settings/alarms', [IotAlarmRuleController::class, 'index'])->name('iot.settings.alarms');
    Route::post('/iot/settings/alarms', [IotAlarmRuleController::class, 'store'])->name('iot.settings.alarms.store');
    Route::post('/iot/settings/alarms/{ruleId}/update', [IotAlarmRuleController::class, 'update'])->name('iot.settings.alarms.update');
    Route::post('/iot/settings/alarms/{ruleId}/toggle', [IotAlarmRuleController::class, 'toggle'])->name('iot.settings.alarms.toggle');
    Route::delete('/iot/settings/alarms/{ruleId}', [IotAlarmRuleController::class, 'destroy'])->name('iot.settings.alarms.destroy');

    Route::get('/iot/settings/users', [IotUserController::class, 'index'])->name('iot.settings.users');
    Route::post('/iot/settings/users', [IotUserController::class, 'store'])->name('iot.settings.users.store');
    Route::post('/iot/settings/users/{user}/update', [IotUserController::class, 'update'])->name('iot.settings.users.update');
    Route::post('/iot/settings/users/{user}/toggle', [IotUserController::class, 'toggle'])->name('iot.settings.users.toggle');
    Route::post('/iot/settings/users/{user}/password', [IotUserController::class, 'updatePassword'])->name('iot.settings.users.password');

    /*
    |--------------------------------------------------------------------------
    | Reportes Automáticos
    |--------------------------------------------------------------------------
    */

    Route::get('/iot/reports', [ScheduledReportController::class, 'index'])
        ->name('iot.reports');

    Route::get('/iot/reports/create', [ScheduledReportController::class, 'create'])
        ->name('iot.reports.create');

    Route::post('/iot/reports', [ScheduledReportController::class, 'store'])
        ->name('iot.reports.store');

    Route::delete('/iot/reports/{report}', [ScheduledReportController::class, 'destroy'])
        ->name('iot.reports.destroy');
});