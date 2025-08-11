<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalController extends Controller
{
    public function opcacheClear(Request $request): JsonResponse
    {
        // Проверка подписи
        $timestamp = $request->get('timestamp');
        $signature = $request->get('signature');
        
        if (!$timestamp || !$signature) {
            return response()->json(['success' => false, 'message' => 'Missing signature'], 401);
        }
        
        // Проверка времени (запрос не старше 60 секунд)
        if (abs(time() - $timestamp) > 60) {
            return response()->json(['success' => false, 'message' => 'Request expired'], 401);
        }
        
        // Проверка подписи
        $appKey = config('app.key');
        $expectedSignature = hash_hmac('sha256', "opcache-clear:{$timestamp}", $appKey);
        
        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }
        if (!function_exists('opcache_reset')) {
            return response()->json([
                'success' => false, 
                'message' => 'OPcache extension not available'
            ]);
        }
        
        if (!ini_get('opcache.enable')) {
            return response()->json([
                'success' => false, 
                'message' => 'OPcache not enabled'
            ]);
        }
        
        $cleared = opcache_reset();
        
        return response()->json([
            'success' => $cleared,
            'message' => $cleared ? 'OPcache cleared successfully' : 'Failed to clear OPcache',
            'timestamp' => now()->toISOString(),
            'sapi' => php_sapi_name()
        ]);
    }
}
