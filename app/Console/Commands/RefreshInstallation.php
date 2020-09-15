<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshInstallation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installation:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all data and refresh installation';

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

        // Clear media dir
        $contents = \Storage::disk('media')->listContents('');
        foreach ($contents as $content) {
            if ($content['type'] == 'dir') {
                \Storage::disk('media')->deleteDir($content['path']);
            }
        }

        \Artisan::call('cache:clear');
        echo 'cache:clear - Done!' . PHP_EOL;

        \Artisan::call('view:clear');
        echo 'view:clear - Done!' . PHP_EOL;

        \Artisan::call('config:clear');
        echo 'config:clear - Done!' . PHP_EOL;

        \Artisan::call('migrate:refresh', ['--force' => true]);
        echo 'migrate:refresh - Done!' . PHP_EOL;

        \Artisan::call('db:seed', ['--force' => true]);
        echo 'db:seed - Done!' . PHP_EOL;

        echo 'All done!' . PHP_EOL . PHP_EOL;
    }
}
