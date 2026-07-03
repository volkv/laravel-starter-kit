<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class TelegramService
{
    protected string $botToken;
    protected string $chatId;

    public function __construct()
    {
        $this->botToken = config('telegram.bot_token') ?? '';
        $this->chatId = config('telegram.chat_id') ?? '';
    }

    public function sendMessage(string $message): bool
    {
        if (!$this->botToken || !$this->chatId) {
            Log::warning('Telegram bot token or chat ID not configured');
            return false;
        }

        try {
            $response = Http::timeout(5)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
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
        if (!$this->allowedByRateLimiter()) {
            return false;
        }

        $message = $this->formatErrorMessage($exception, $context);
        return $this->sendMessage($message);
    }

    /**
     * Cap error notifications so an error storm doesn't flood Telegram
     * (and doesn't stall every failing request on the HTTP call).
     */
    private function allowedByRateLimiter(): bool
    {
        try {
            $allowed = RateLimiter::attempt('telegram-error-notification', 20, fn () => true);

            if (!$allowed) {
                Log::warning('Telegram error notification skipped: rate limit reached');
            }

            return (bool) $allowed;
        } catch (\Throwable $e) {
            // The limiter's cache store is down (possibly the error being
            // reported) — better to send unthrottled than to lose the alert.
            Log::warning('Telegram rate limiter unavailable, sending without throttle', [
                'message' => $e->getMessage(),
            ]);

            return true;
        }
    }

    private function formatErrorMessage(\Throwable $exception, array $context = []): string
    {
        $message = "🚨 <b>Application Error</b>\n\n";
        $message .= "<b>Environment:</b> " . e(app()->environment()) . "\n";
        $message .= "<b>Error:</b> " . e(get_class($exception)) . "\n";
        $message .= "<b>Message:</b> " . e(Str::limit($exception->getMessage(), 700)) . "\n";
        $message .= "<b>File:</b> " . e($exception->getFile() . ":" . $exception->getLine()) . "\n";

        if (!empty($context['url'])) {
            $message .= "<b>URL:</b> " . e($context['url']) . "\n";
        }

        if (!empty($context['user_id'])) {
            $message .= "<b>User ID:</b> " . e((string) $context['user_id']) . "\n";
        }

        if (!empty($context['request_id'])) {
            $message .= "<b>Request ID:</b> " . e((string) $context['request_id']) . "\n";
        }

        $traceLines = explode("\n", $exception->getTraceAsString());
        $limitedTrace = implode("\n", array_slice($traceLines, 0, 3));
        $trace = "\n<b>Stack trace (first 3 lines):</b>\n"
            . "<code>" . e(Str::limit($limitedTrace, 1000)) . "</code>";

        // Telegram hard limit is 4096 chars; drop the trace rather than cut
        // mid-tag/mid-entity, which would make the whole message unparseable.
        if (mb_strlen($message . $trace) <= 4000) {
            $message .= $trace;
        }

        return $message;
    }
}
