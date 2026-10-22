<?php

use App\Http\Controllers\Api\ResendEmailVerificationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverLicenseController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\UserManagementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ParkingDashboardController;
use App\Http\Controllers\Api\VehicleAuthorizationController;
use App\Http\Controllers\Api\ParkingOperationsController;
use App\Http\Controllers\Api\ViolationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RfidReaderController;
use App\Http\Controllers\Api\AccessEventController;
use App\Http\Controllers\Api\VisitorAccessController;
use App\Http\Controllers\Api\DeviceSimulatorController;
use App\Http\Controllers\Api\GuestAccountController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\SystemSettingsController;
use App\Http\Controllers\Api\ParkingSlotSimulationController;



Route::get('/departments', [DepartmentController::class, 'index']);

Route::get('/registration/options', [\App\Http\Controllers\Api\RegistrationIdController::class, 'options']);
Route::post('/registration/id-check', [\App\Http\Controllers\Api\RegistrationIdController::class, 'check'])->middleware('throttle:registration-id-read');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:regular-registration');

// Guest (visitor) self-registration.
Route::post('/guest/register', [GuestAccountController::class, 'register'])
    ->middleware('throttle:5,1,api-action-1:');

// Registered RFID gate readers authenticate with X-Reader-Code / X-Reader-Key.
Route::post('/devices/rfid/scan', [RfidReaderController::class, 'scan'])
    ->middleware('throttle:120,1,api-action-2:');

Route::post('/email/verify-code', \App\Http\Controllers\Api\VerifyEmailCodeController::class)->middleware('throttle:10,1,api-action-3:');

// Email verification
Route::get(
    '/email/verify/{id}/{hash}',
    [EmailVerificationController::class, 'verify']
)
    ->middleware(['signed', 'throttle:10,1,api-action-4:'])
    ->name('verification.verify');
Route::post(
    '/email/verification-notification',
    ResendEmailVerificationController::class
)->middleware('throttle:5,1,api-action-5:');

// Login
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1,api-action-6:');

// Public password recovery routes
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:5,1,api-action-7:');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:5,1,api-action-8:');

// Authenticated routes
Route::middleware(['auth:sanctum', \App\Http\Middleware\RecordHistoryContext::class.':actor', \App\Http\Middleware\AccountAccess::class])->group(function () {
    Route::get(
    '/vehicles/{id}/photo',
    [\App\Http\Controllers\Api\VehicleRegistrationController::class, 'photo']
)->whereNumber('id');

Route::post(
    '/vehicles/{id}/photo',
    [\App\Http\Controllers\Api\VehicleRegistrationController::class, 'uploadPhoto']
)->whereNumber('id')->middleware('throttle:vehicle-plate-submit');

Route::post('/vehicles/{id}/resubmit', [\App\Http\Controllers\Api\VehicleRegistrationController::class, 'resubmit'])
    ->whereNumber('id')->middleware('throttle:vehicle-plate-submit');

    Route::patch(
    '/vehicles/authorizations/{id}/review',
    [\App\Http\Controllers\Api\VehicleAuthorizationController::class, 'review']
)->whereNumber('id');
    Route::get(
    '/vehicles',
    [\App\Http\Controllers\Api\VehicleRegistrationController::class, 'index']
);

Route::post(
    '/vehicles',
    [\App\Http\Controllers\Api\VehicleRegistrationController::class, 'store']
)->middleware('throttle:vehicle-plate-submit');
    Route::get('/admin/users/{id}/school-id', [UserManagementController::class, 'schoolId'])->whereNumber('id');
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [\App\Http\Controllers\Api\AccountProfileController::class, 'show']);
    Route::patch('/profile', [\App\Http\Controllers\Api\AccountProfileController::class, 'update']);
    Route::post('/profile/password', [\App\Http\Controllers\Api\AccountProfileController::class, 'password'])->middleware('throttle:5,1,profile-password:');

    // Change markers for the shared frontend refresh (polled every few seconds while a tab is visible).
    Route::get('/sync/revision', [SyncController::class, 'revision'])->middleware('throttle:120,1,api-action-9:');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/parking/dashboard', [ParkingDashboardController::class, 'index']);
    Route::get('/vehicles/authorizations', [VehicleAuthorizationController::class, 'index']);
    Route::get('/vehicles/my-authorizations', [VehicleAuthorizationController::class, 'mine']);
    Route::patch('/vehicles/authorizations/{id}/suspend', [VehicleAuthorizationController::class, 'suspend'])
        ->whereNumber('id');
    Route::patch('/vehicles/authorizations/{id}/lift-suspension', [VehicleAuthorizationController::class, 'liftSuspension'])
        ->whereNumber('id');

    // Driver's license submissions (separate from vehicle authorization and RFID).
    Route::get('/licenses', [DriverLicenseController::class, 'index']);
    Route::get('/licenses/mine', [DriverLicenseController::class, 'mine']);
    Route::post('/licenses', [DriverLicenseController::class, 'submit'])
        ->middleware('throttle:license-submit');
    Route::get('/licenses/{id}/file', [DriverLicenseController::class, 'file'])
        ->whereNumber('id');
    Route::patch('/licenses/{id}/review', [DriverLicenseController::class, 'review'])
        ->whereNumber('id');
    Route::post('/licenses/{id}/recheck', [DriverLicenseController::class, 'recheck'])
        ->whereNumber('id')->middleware('throttle:license-recheck');

    // Parking operations: two parking areas, slot assignment, occupancy.
    Route::prefix('parking')->group(function () {
        Route::get('/overview', [ParkingOperationsController::class, 'overview']);
        Route::get('/availability', [ParkingOperationsController::class, 'availability']);
        Route::get('/availability-map', [ParkingOperationsController::class, 'availabilityMap']);
        Route::get('/my-session', [ParkingOperationsController::class, 'mySession']);
        Route::get('/areas/{areaId}/slots', [ParkingOperationsController::class, 'slots'])->whereNumber('areaId');
        Route::get('/map', [ParkingOperationsController::class, 'map']);
        Route::patch('/slots/{slotId}', [ParkingOperationsController::class, 'updateSlot'])->whereNumber('slotId');
        Route::get('/history', [ParkingOperationsController::class, 'history']);
        Route::get('/transactions', [ParkingOperationsController::class, 'transactions']);
        Route::post('/transactions/{id}/observed-slot', [ParkingOperationsController::class, 'observedSlot'])->whereNumber('id');
        Route::post('/entry', [ParkingOperationsController::class, 'entry']);
        Route::post('/exit', [ParkingOperationsController::class, 'exit']);
    });

    // Gate access records (read-only) and RFID credentials.
    Route::get('/access-events', [AccessEventController::class, 'index']);
    Route::get('/rfid/assignments', [AccessEventController::class, 'rfidAssignments']);
    Route::post('/rfid/assignments', [\App\Http\Controllers\Api\RfidAssignmentController::class, 'store'])->middleware('throttle:20,1,api-action-10:');
    Route::patch('/rfid/assignments/{id}/revoke', [\App\Http\Controllers\Api\RfidAssignmentController::class, 'revoke'])->whereNumber('id');


    // Visitor registration, requests, approval and Visitor QR passes.
    Route::prefix('visitors')->group(function () {
        Route::get('/options', [VisitorAccessController::class, 'options']);
        Route::get('/requests', [VisitorAccessController::class, 'requests']);
        Route::post('/requests', [VisitorAccessController::class, 'storeRequest'])->middleware('throttle:30,1,api-action-11:');
        Route::post('/requests/{id}/approve', [VisitorAccessController::class, 'approve'])->whereNumber('id');
        Route::post('/requests/{id}/reject', [VisitorAccessController::class, 'reject'])->whereNumber('id');
        Route::get('/passes', [VisitorAccessController::class, 'passes']);
        Route::get('/passes/lookup', [VisitorAccessController::class, 'lookup']);
        Route::post('/passes/{id}/revoke', [VisitorAccessController::class, 'revoke'])->whereNumber('id');
    });

    Route::post('/visitors/requests/{id}/document-check', [\App\Http\Controllers\Api\VisitorRequestDocumentController::class, 'check'])->whereNumber('id')->middleware('throttle:6,1,visitor-document-check:');

    Route::get('/visitors/requests/{id}/documents/{kind}', [\App\Http\Controllers\Api\VisitorRequestDocumentController::class, 'show'])->whereNumber('id')->whereIn('kind', ['license', 'vehicle']);

    Route::post('/vehicles/{id}/select-for-use', [\App\Http\Controllers\Api\VehicleRegistrationController::class, 'selectForUse'])->whereNumber('id');

    Route::get('/vehicle-releases', [\App\Http\Controllers\Api\VehicleReleaseController::class, 'index']);
    Route::post('/vehicle-releases', [\App\Http\Controllers\Api\VehicleReleaseController::class, 'store'])->middleware('throttle:10,1,vehicle-release:');
    Route::post('/vehicle-releases/{id}/action', [\App\Http\Controllers\Api\VehicleReleaseController::class, 'act'])->whereNumber('id');

    // Guest (visitor) account: own profile, vehicles, requests, passes and visits only.
    Route::prefix('guest')->group(function () {
        Route::get('/profile', [GuestAccountController::class, 'profile']);
        Route::patch('/profile', [GuestAccountController::class, 'updateProfile']);
        Route::get('/vehicles', [GuestAccountController::class, 'vehicles']);
        Route::post('/vehicles', [GuestAccountController::class, 'storeVehicle'])->middleware('throttle:20,1,api-action-12:');
        Route::get('/requests', [GuestAccountController::class, 'requests']);
        Route::post('/requests', [GuestAccountController::class, 'storeRequest'])->middleware('throttle:20,1,api-action-13:');
        Route::post('/requests/{id}/cancel', [GuestAccountController::class, 'cancelRequest'])->whereNumber('id');
        Route::get('/passes', [GuestAccountController::class, 'passes']);
        Route::get('/visits', [GuestAccountController::class, 'visits']);
    });

    // Development-only gate simulator (config/pass.php); 404 unless enabled for the tester.
    Route::get('/dev/simulator', [DeviceSimulatorController::class, 'index']);
    Route::post('/dev/simulator/scan', [DeviceSimulatorController::class, 'scan'])->middleware('throttle:60,1,api-action-14:');

    Route::get('/violation-types', [ViolationController::class, 'types']);
    Route::get('/violations', [ViolationController::class, 'index']);
    Route::post('/violations', [ViolationController::class, 'store']);
    Route::patch('/violations/{id}', [ViolationController::class, 'update'])->whereNumber('id');

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->whereNumber('id');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Admin user management
    Route::prefix('admin')->group(function () {
        Route::get('/parking-layout/export', [\App\Http\Controllers\Api\ParkingLayoutExportController::class, 'show']);
        Route::get('/record-history', [\App\Http\Controllers\Api\RecordHistoryController::class, 'index']);
        Route::get('/record-history/{id}/documents/{field}', [\App\Http\Controllers\Api\RecordHistoryController::class, 'document'])->whereNumber('id');
        Route::get('/record-archives/{entity}', [\App\Http\Controllers\Api\RecordArchiveController::class, 'index'])->whereIn('entity', ['users', 'vehicles']);
        Route::post('/record-archives/{entity}/{id}/{action}', [\App\Http\Controllers\Api\RecordArchiveController::class, 'change'])->whereIn('entity', ['users', 'vehicles'])->whereNumber('id')->whereIn('action', ['archive', 'restore'])->middleware('throttle:20,1,record-archive');
        Route::get('/audit-alerts', [\App\Http\Controllers\Api\AuditPriorityController::class, 'index']);
        Route::post('/audit-alerts/{key}/review', [\App\Http\Controllers\Api\AuditPriorityController::class, 'review'])->where('key', '[a-f0-9]{64}')->middleware('throttle:20,1,audit-alert-review');

        Route::get('/reports', [\App\Http\Controllers\Api\AdminReportController::class, 'index']);
        Route::get('/reports/export', [\App\Http\Controllers\Api\AdminReportController::class, 'export'])->middleware('throttle:10,1,admin-report-export:');
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/parking/occupancy', [AdminDashboardController::class, 'occupancy']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/system-settings', [SystemSettingsController::class, 'index']);
        Route::put('/system-settings', [SystemSettingsController::class, 'update']);
        // Separate development slot simulation; not parking transaction occupancy.
        Route::get('/parking/slots', [ParkingSlotSimulationController::class, 'index']);
        Route::patch('/parking/slots', [ParkingSlotSimulationController::class, 'update']);

        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [\App\Http\Controllers\Api\PrivilegedAccountController::class, 'store'])->middleware('throttle:privileged-invite');

        Route::patch(
            '/users/{id}/approve',
            [UserManagementController::class, 'approve']
        );

        Route::patch(
            '/users/{id}/reject',
            [UserManagementController::class, 'reject']
        );
    });
});

// PASS_PRIVILEGED_ACCOUNT_SETUP
require __DIR__.'/privileged_accounts.php';
