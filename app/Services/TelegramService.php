<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;
    protected string $chatId;

    public function __construct()
    {
        $this->botToken = config('telegram.bot_token');
        $this->chatId = config('telegram.chat_id');
    }

    public function sendMessage(string $message): bool
    {
        if (!$this->botToken || !$this->chatId) {
            Log::warning('Telegram bot token or chat ID not configured');
            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Failed to send Telegram message', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Exception sending Telegram message', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function sendErrorNotification(\Throwable $exception, array $context = []): bool
    {
        $message = $this->formatErrorMessage($exception, $context);
        return $this->sendMessage($message);
    }

    private function formatErrorMessage(\Throwable $exception, array $context = []): string
    {
        $message = "🚨 <b>Application Error</b>\n\n";
        $message .= "<b>Environment:</b> " . app()->environment() . "\n";
        $message .= "<b>Error:</b> " . get_class($exception) . "\n";
        $message .= "<b>Message:</b> " . $exception->getMessage() . "\n";
        $message .= "<b>File:</b> " . $exception->getFile() . ":" . $exception->getLine() . "\n";
        
        if (!empty($context['url'])) {
            $message .= "<b>URL:</b> " . $context['url'] . "\n";
        }
        
        if (!empty($context['user_id'])) {
            $message .= "<b>User ID:</b> " . $context['user_id'] . "\n";
        }

        if (!empty($context['request_id'])) {
            $message .= "<b>Request ID:</b> " . $context['request_id'] . "\n";
        }

        $message .= "\n<b>Stack trace (first 3 lines):</b>\n";
        $traceLines = explode("\n", $exception->getTraceAsString());
        $limitedTrace = array_slice($traceLines, 0, 3);
        $message .= "<code>" . implode("\n", $limitedTrace) . "</code>";

        if (strlen($message) > 4000) {
            $message = substr($message, 0, 4000) . '...';
        }

        return $message;
    }
}