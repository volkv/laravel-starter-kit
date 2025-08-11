<?php

namespace App\Console\Commands;

use App\Jobs\UpdateKursAli;
use Illuminate\Console\Command;

class Test extends Command
{
    protected $signature = 'volkv:test';

    public function handle(): void
    {
        // make cmd-test to test telegram notifications
         5 / 0 ;
    }
}
