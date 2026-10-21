<?php

use App\Http\Controllers\Api\PrivilegedAccountController;
use App\Http\Middleware\AccountAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', \App\Http\Middleware\RecordHistoryContext::class.':actor', AccountAccess::class])->group(function () {
    Route::post('/admin/account-invitations', [PrivilegedAccountController::class, 'store'])->middleware('throttle:privileged-invite');
    Route::get('/admin/account-invitations/{id}', [PrivilegedAccountController::class, 'showInvitation'])->whereNumber('id');
    Route::post('/admin/account-invitations/{id}/resend', [PrivilegedAccountController::class, 'resendInvitation'])->whereNumber('id')->middleware('throttle:privileged-invite');
    Route::get('/account/setup', [PrivilegedAccountController::class, 'status']);
    Route::post('/account/setup/resend-code', [PrivilegedAccountController::class, 'resendCode'])->middleware('throttle:privileged-code');
    Route::post('/account/setup/verify', [PrivilegedAccountController::class, 'verify'])->middleware('throttle:privileged-verify');
    Route::post('/account/setup/complete', [PrivilegedAccountController::class, 'complete'])->middleware('throttle:privileged-complete');
});
