<?php

use App\Http\Middleware\AuthenticateMobileInvoiceCollector;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'invoice.mobile' => AuthenticateMobileInvoiceCollector::class,
        ]);

        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '*'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Ukuran total file yang diunggah terlalu besar melebihi batas maksimal server. Silakan kompres atau perkecil ukuran file Anda.',
                ], 413);
            }

            return back()->with('error', 'Ukuran total file yang diunggah terlalu besar melebihi batas maksimal server. Silakan kompres atau perkecil ukuran file Anda sebelum mengunggah kembali.');
        });
    })->create();
