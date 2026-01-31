<?php

use Illuminate\Support\Facades\Route;
use NafeesAhmed\Enapi\Laravel\LaravelApiController;

$controller = app(LaravelApiController::class);
$controller->registerModels(
    config('enapi.models_path', app_path('Models')),
    config('enapi.models_namespace', 'App\\Models')
);

$prefix = config('enapi.api_prefix', 'api');
$middleware = config('enapi.middleware', ['api']);

// Register routes for each model
foreach ($controller->getModels() as $resource => $modelClass) {
    Route::prefix($prefix)
        ->middleware($middleware)
        ->group(function () use ($resource, $controller) {
            Route::get($resource, function () use ($controller, $resource) {
                return $controller->index($resource);
            });
            
            Route::get("$resource/{id}", function ($id) use ($controller, $resource) {
                return $controller->show($resource, $id);
            });
            
            Route::post($resource, function () use ($controller, $resource) {
                return $controller->store(request(), $resource);
            });
            
            Route::put("$resource/{id}", function ($id) use ($controller, $resource) {
                return $controller->update(request(), $resource, $id);
            });
            
            Route::delete("$resource/{id}", function ($id) use ($controller, $resource) {
                return $controller->destroy($resource, $id);
            });
        });
}
