<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OpcacheClear extends Command
{
    protected $signature = 'opcache:clear';

    protected $description = 'Clear OPcache';

    public function handle()
    {
        // Сбрасываем CLI OPcache
        if (function_exists('opcache_reset') && ini_get('opcache.enable')) {
            opcache_reset();
            $this->info('CLI OPcache cleared');
        }

        // Делаем подписанный HTTP запрос к nginx
        $this->info('Making signed HTTP request to clear web OPcache...');
        
        try {
            // Генерируем подпись с timestamp
            $timestamp = time();
            $appKey = config('app.key');
            $signature = hash_hmac('sha256', "opcache-clear:{$timestamp}", $appKey);
            
            // Формируем URL с параметрами
            $url = "https://nginx:443/internal/opcache-clear?timestamp={$timestamp}&signature=" . urlencode($signature);
            
            $result = shell_exec('curl -s -m 10 -k "' . $url . '" 2>&1');
            
            if ($result === null) {
                throw new \Exception('curl command failed');
            }
            
            $data = json_decode($result, true);
            
            if ($data && $data['success']) {
                $this->info('Web OPcache cleared via ' . ($data['sapi'] ?? 'unknown SAPI'));
                $this->info('OPcache cleared for both CLI and web environments');
                return 0;
            } else {
                $message = $data['message'] ?? trim($result);
                $this->error('Failed to clear web OPcache: ' . $message);
                $this->info('CLI OPcache was cleared, but web OPcache may still be cached');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('Failed to clear web OPcache: ' . $e->getMessage());
            $this->info('CLI OPcache was cleared, but web OPcache may still be cached');
            return 1;
        }
    }
}