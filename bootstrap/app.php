<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (Throwable $e) {
            // Skip HTTP exceptions with status codes we don't want to report
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $statusCode = $e->getStatusCode();
                // Skip client errors (400-499) like 404, 403, etc.
                if ($statusCode >= 400 && $statusCode < 500) {
                    return;
                }
            }

            // Skip validation exceptions
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return;
            }

            // Skip authentication exceptions
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return;
            }

            // Try to send to Telegram
            try {
                $telegramService = app(\App\Services\TelegramService::class);
                $context = [
                    'url' => request()->fullUrl(),
                    'user_id' => auth()->id(),
                    'request_id' => request()->header('X-Request-ID') ?: uniqid(),
                ];
                $telegramService->sendErrorNotification($e, $context);
            } catch (\Exception $telegramException) {
                // If Telegram fails, just log it but don't break the error reporting
                \Illuminate\Support\Facades\Log::error('Failed to send error to Telegram', [
                    'original_error' => $e->getMessage(),
                    'telegram_error' => $telegramException->getMessage(),
                ]);
            }
        });
    })->create();
