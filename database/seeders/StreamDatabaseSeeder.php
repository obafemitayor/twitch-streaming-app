<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Shared\StreamSeeder;
use App\Implementation\MysqlDatabaseProvider;

class StreamDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        echo 'Now Seeding....';
        $databaseProvider = new MysqlDatabaseProvider;
        $seeder = new StreamSeeder();
        $seeder->seed_database($databaseProvider);
        $databaseProvider->save_new_data();
        echo 'Seeding Complete....';
    }
}
