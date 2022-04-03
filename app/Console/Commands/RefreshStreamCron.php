<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Shared\StreamSeeder;
use App\Implementation\MysqlDatabaseProvider;
use App\Shared\StreamDatabaseLock;

class RefreshStreamCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refreshstream:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $databaseProvider = new MysqlDatabaseProvider;
        $databaseProvider->delete_current_data();
        $seeder = new StreamSeeder();
        $seeder->seed_database($databaseProvider);
        $running_operation = null;
        do {
            $running_operation = StreamDatabaseLock::checkIfLockExist();
        } while ($running_operation == true);
        StreamDatabaseLock::aquireLock();
        $databaseProvider->save_new_data();
        StreamDatabaseLock::releaseLock();
    }
}
