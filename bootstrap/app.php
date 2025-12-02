<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Loader automático de módulos
            foreach (glob(base_path('modules/*/routes.php')) as $file) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group($file);
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.tenant' => \App\Http\Middleware\CheckTenant::class,
            'check.permission' => \App\Http\Middleware\CheckPermission::class,
            'assign.tenant' => \App\Http\Middleware\AssignTenantId::class,
        ]);
        
        $middleware->append(\App\Http\Middleware\HandlePutFormData::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

