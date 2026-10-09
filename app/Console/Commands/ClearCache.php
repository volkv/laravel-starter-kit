<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Process;

class ClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'volkv:cache {--noide}';


    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->execShellWithPrettyPrint('composer dump-autoload -o');

        if (App::environment() == 'local' && !$this->option('noide')) {

            $this->execShellWithPrettyPrint('php artisan ide-helper:generate');
            $this->execShellWithPrettyPrint('php artisan ide-helper:models -W');
            $this->execShellWithPrettyPrint('php artisan ide-helper:meta');

        }

        $this->execShellWithPrettyPrint('php artisan optimize:clear');

        if (config('app.php_opcache_enable')) {
            $this->execShellWithPrettyPrint('php artisan opcache:clear');
        }

        $this->execShellWithPrettyPrint('php artisan queue:restart');
        return "Cache cleared";
    }

    /**
     * Exec shell with pretty print.
     *
     * @param string $command
     *
     * @return mixed
     */
    public function execShellWithPrettyPrint($command)
    {
        $this->info($command);
        // A failed step fails the whole command: shell_exec() hid exit codes, so a deploy could
        // report success with a stale OPcache or a broken autoload.
        $result = Process::path(base_path())->timeout(300)->run($command);
        if ($result->output()) {
            $this->info($result->output());
        }
        $result->throw();
    }
}
