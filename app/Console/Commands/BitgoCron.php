<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BitgoCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'BitgoCron:bitgocron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unlock Bitgo Wallet';

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
        
    }
}
