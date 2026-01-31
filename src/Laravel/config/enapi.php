<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto Register Routes
    |--------------------------------------------------------------------------
    |
    | Automatically register API routes for all models in the models directory.
    | If false, you can manually register routes in your routes file.
    |
    */
    'auto_register_routes' => env('ENAPI_AUTO_ROUTES', true),

    /*
    |--------------------------------------------------------------------------
    | Models Path
    |--------------------------------------------------------------------------
    |
    | The directory where your models are located.
    |
    */
    'models_path' => app_path('Models'),

    /*
    |--------------------------------------------------------------------------
    | Models Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace for your models.
    |
    */
    'models_namespace' => 'App\\Models',

    /*
    |--------------------------------------------------------------------------
    | API Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for all API routes. Default is 'api'.
    |
    */
    'api_prefix' => env('ENAPI_PREFIX', 'api'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to all API routes.
    |
    */
    'middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Excluded Models
    |--------------------------------------------------------------------------
    |
    | Models to exclude from automatic API generation.
    |
    */
    'excluded_models' => [
        // 'User',
    ],
];
