<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OpcacheClearMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем наличие триггера для очистки opcache
        $triggerFile = storage_path('framework/opcache_clear_trigger');
        
        if (file_exists($triggerFile) && function_exists('opcache_reset') && ini_get('opcache.enable')) {
            // Очищаем opcache
            if (opcache_reset()) {
                // Удаляем файл триггер после успешной очистки
                unlink($triggerFile);
                
                // Логируем событие (опционально)
                if (app()->environment('local')) {
                    \Log::info('OPcache cleared by trigger in web environment', [
                        'sapi' => php_sapi_name(),
                        'request_uri' => $request->getRequestUri()
                    ]);
                }
            }
        }
        
        return $next($request);
    }
}
