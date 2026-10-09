<?php

namespace Tests\Feature;

use App\Services\TelegramService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use PDOException;
use Tests\TestCase;

class ErrorReportRedactionTest extends TestCase
{
    public function test_query_exception_is_reported_without_bound_values_or_driver_detail(): void
    {
        $secret = 'secret-person@example.com';
        $sql = 'insert into comments (author_email) values (?)';
        $driverError = new PDOException(
            "SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint \"x\"\n"
            ."DETAIL:  Key (author_email)=({$secret}) already exists."
        );
        $original = new QueryException('pgsql', $sql, [$secret], $driverError);
        $this->assertStringContainsString($secret, $original->getMessage(), 'precondition: Laravel puts the value into the message');

        $sentToTelegram = null;
        $this->mock(TelegramService::class)
            ->shouldReceive('sendErrorNotification')
            ->andReturnUsing(function (\Throwable $e) use (&$sentToTelegram) {
                $sentToTelegram = $e;

                return true;
            });

        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged) {
            $logged[] = $event->message.' '.(($event->context['exception'] ?? null)?->getMessage() ?? '');
        });

        app(ExceptionHandler::class)->report($original);

        $this->assertNotNull($sentToTelegram, 'the error still reaches Telegram');
        $this->assertStringNotContainsString($secret, $sentToTelegram->getMessage());
        $this->assertStringContainsString($sql, $sentToTelegram->getMessage(), 'the SQL template is kept for debugging');
        $this->assertNull($sentToTelegram->getPrevious(), 'the original is not chained: loggers print the chain');

        $this->assertNotEmpty($logged, 'the error is still logged');
        foreach ($logged as $line) {
            $this->assertStringNotContainsString($secret, $line);
        }
    }
}
