<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\BlueprintFramework\Extensions\{identifier}\StoreController;
use Pterodactyl\BlueprintFramework\Extensions\{identifier}\SplitterController;

// Store endpoints
Route::get('/store/balance', [StoreController::class, 'balance']);
Route::get('/store/categories', [StoreController::class, 'categories']);
Route::post('/store/purchase', [StoreController::class, 'purchase']);
Route::post('/store/renew', [StoreController::class, 'renew']);
Route::get('/store/expirations', [StoreController::class, 'expirations']);
Route::get('/store/transactions', [StoreController::class, 'transactions']);
Route::get('/store/server-package/{uuid}', [StoreController::class, 'serverPackage']);
Route::post('/store/toggle-auto-renew', [StoreController::class, 'toggleAutoRenew']);
Route::post('/store/change-package', [StoreController::class, 'changePackage']);
Route::post('/store/change-billing', [StoreController::class, 'changeBilling']);
Route::post('/store/extend-hours', [StoreController::class, 'extendHours']);
Route::post('/store/process-auto-renewals', [StoreController::class, 'processAutoRenewals']);
Route::get('/store/settings', [StoreController::class, 'settings']);

// Splitter endpoints
Route::get('/splitter/resources', [SplitterController::class, 'resources']);
Route::get('/splitter/badge', [SplitterController::class, 'badge']);
Route::get('/splitter/eggs', [SplitterController::class, 'eggs']);
Route::get('/splitter/nodes', [SplitterController::class, 'nodes']);
Route::post('/splitter/create', [SplitterController::class, 'create']);
Route::get('/splitter/servers', [SplitterController::class, 'servers']);
Route::post('/splitter/update-server', [SplitterController::class, 'updateServer']);
Route::post('/splitter/delete-server', [SplitterController::class, 'deleteServer']);
Route::get('/splitter/server-info/{uuid}', [SplitterController::class, 'serverInfo']);

// Coupon
Route::post('/store/apply-coupon', [StoreController::class, 'applyCoupon']);

// Free resources
Route::post('/store/claim-free-resources', [StoreController::class, 'claimFreeResources']);
Route::get('/store/free-resources-info', [StoreController::class, 'freeResourcesInfo']);

// User info
Route::get('/user/is-admin', [StoreController::class, 'isAdmin']);
