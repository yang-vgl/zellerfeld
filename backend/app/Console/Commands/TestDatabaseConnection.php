<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(
    name: 'db:test-connection',
    description: 'Test the default database connection (SQLite)'
)]
class TestDatabaseConnection extends Command
{
    public function handle(): int
    {
        $this->info('Testing database connection using the default connection...');

        try {
            $result = DB::select('select 1 as value');
        } catch (Throwable $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Database connection OK. Result: ' . json_encode($result));

        return self::SUCCESS;
    }
}
