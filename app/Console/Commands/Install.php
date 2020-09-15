<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete cache and migrate and seed database';

    /**
     * Create a new command instance.
     *
     * @return void
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
				# https://stackoverflow.com/a/28898174
				if(!defined('STDIN'))  define('STDIN',  fopen('php://stdin',  'rb'));
				if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'wb'));
				if(!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));
      
        \Artisan::call('config:cache');
      
        \Artisan::call('config:clear');

        \Artisan::call('cache:clear');

        \Artisan::call('view:clear');

        \Artisan::call('key:generate', ['--force' => true]);

        \Artisan::call('jwt:secret', ['--force' => true]);

        \Artisan::call('migrate:refresh', ['--force' => true]);

        \Artisan::call('config:cache');
      
        \Artisan::call('config:clear');

        \Artisan::call('cache:clear');

        \Artisan::call('db:seed', ['--force' => true]);
    }
}
