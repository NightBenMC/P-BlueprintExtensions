<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\BlueprintFramework\Extensions\fileflow\FileFlowController;
use Pterodactyl\Http\Middleware\Activity\ServerSubject;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;

Route::group([
    'prefix' => '/servers/{server}',
    'middleware' => [
        ServerSubject::class,
        AuthenticateServerAccess::class,
        ResourceBelongsToServer::class,
    ],
], function () {
    Route::get('/search', [FileFlowController::class, 'searchFiles']);
    Route::get('/commands', [FileFlowController::class, 'getCommands']);
    Route::post('/commands', [FileFlowController::class, 'addCommand']);
    Route::delete('/commands/{id}', [FileFlowController::class, 'deleteCommand']);
});
