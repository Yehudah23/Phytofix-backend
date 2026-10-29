<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CopyMySqlDataToPostgres extends Command
{
    protected $signature = 'database:copy-mysql-to-postgres {--force : Run without an interactive confirmation}';

    protected $description = 'Copy production MySQL application data into an empty PostgreSQL database';

    /**
     * @var list<string>
     */
    private const TABLES = [
        'users',
        'password_reset_tokens',
        'products',
        'admins',
        'orders',
        'carts',
        'order_items',
        'product_routines',
        'routine_steps',
        'prescribed_routines',
        'prescribed_routine_steps',
        'user_products',
        'news_posts',
        'team_members',
        'personal_access_tokens',
        'notifications',
        'admin_notifications',
        'contact_messages',
    ];

    /**
     * @var list<string>
     */
    private const TRANSIENT_TABLES = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'migrations',
        'sessions',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const BOOLEAN_COLUMNS = [
        'products' => ['featured'],
        'team_members' => ['is_active'],
        'notifications' => ['is_read'],
        'admin_notifications' => ['is_read'],
    ];

    public function handle(): int
    {
        $source = DB::connection('mysql_source');
        $target = DB::connection('pgsql');
        $targetSchema = Schema::connection('pgsql');

        if (! $targetSchema->hasTable('database_imports')) {
            $this->error('Run the PostgreSQL migrations before copying data.');

            return self::FAILURE;
        }

        if ($target->table('database_imports')->where('name', 'mysql_to_postgres')->exists()) {
            $this->info('MySQL data has already been copied to PostgreSQL.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Copy production MySQL records to PostgreSQL?')) {
            return self::FAILURE;
        }

        $sourceSchema = Schema::connection('mysql_source');
        $handledTables = [...self::TABLES, ...self::TRANSIENT_TABLES];
        $unhandledTables = array_diff($sourceSchema->getTableListing(schemaQualified: false), $handledTables);

        if ($unhandledTables !== []) {
            $this->error('The MySQL database contains tables that are not configured for import: '.implode(', ', $unhandledTables).'.');

            return self::FAILURE;
        }

        $source->beginTransaction();

        try {
            $sourceCounts = [];

            foreach (self::TABLES as $table) {
                $sourceHasTable = $sourceSchema->hasTable($table);
                $targetHasTable = $targetSchema->hasTable($table);

                if (! $sourceHasTable && ! $targetHasTable) {
                    $sourceCounts[$table] = 0;

                    continue;
                }

                if (! $targetHasTable) {
                    $source->rollBack();
                    $this->error("The PostgreSQL table [{$table}] is missing. Run migrations before copying data.");

                    return self::FAILURE;
                }

                if ($target->table($table)->count() > 0) {
                    $source->rollBack();
                    $this->error("The PostgreSQL table [{$table}] is not empty. No data was copied.");

                    return self::FAILURE;
                }

                $sourceCounts[$table] = $sourceHasTable
                    ? (int) $source->table($table)->count()
                    : 0;
            }

            $target->transaction(function () use ($source, $target, $sourceSchema, $targetSchema, $sourceCounts): void {
                foreach (self::TABLES as $table) {
                    if (! $sourceSchema->hasTable($table)) {
                        continue;
                    }

                    $sourceColumns = $sourceSchema->getColumnListing($table);
                    $targetColumns = $targetSchema->getColumnListing($table);
                    $columns = array_values(array_intersect($sourceColumns, $targetColumns));
                    $orderColumn = $table === 'password_reset_tokens' ? 'email' : 'id';
                    $unmappedColumns = array_diff($sourceColumns, $targetColumns);

                    if ($unmappedColumns !== []) {
                        throw new \RuntimeException(
                            "The PostgreSQL table [{$table}] is missing source columns: ".implode(', ', $unmappedColumns).'.',
                        );
                    }

                    if (! in_array($orderColumn, $columns, true)) {
                        throw new \RuntimeException("The source table [{$table}] does not have the expected [{$orderColumn}] column.");
                    }

                    $source->table($table)
                        ->select($columns)
                        ->orderBy($orderColumn)
                        ->chunk(500, function ($records) use ($target, $table): void {
                            $rows = $records->map(function (object $record) use ($table): array {
                                $row = (array) $record;

                                foreach (self::BOOLEAN_COLUMNS[$table] ?? [] as $column) {
                                    if (array_key_exists($column, $row) && $row[$column] !== null) {
                                        $row[$column] = (bool) $row[$column];
                                    }
                                }

                                return $row;
                            })->all();

                            $target->table($table)->insert($rows);
                        });

                    $copiedCount = (int) $target->table($table)->count();

                    if ($copiedCount !== $sourceCounts[$table]) {
                        throw new \RuntimeException("Row-count verification failed for [{$table}].");
                    }

                    $this->resetSequence($target, $table, $targetColumns);
                    $this->line("Copied {$copiedCount} rows from [{$table}].");
                }

                $target->table('database_imports')->insert([
                    'name' => 'mysql_to_postgres',
                    'row_counts' => json_encode($sourceCounts, JSON_THROW_ON_ERROR),
                    'completed_at' => now(),
                ]);
            });

            $source->commit();
        } catch (Throwable $exception) {
            if ($source->transactionLevel() > 0) {
                $source->rollBack();
            }

            throw $exception;
        }

        $this->info('MySQL data was copied and verified. The MySQL source was not modified.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $columns
     */
    private function resetSequence(Connection $target, string $table, array $columns): void
    {
        if ($target->getDriverName() !== 'pgsql' || ! in_array('id', $columns, true)) {
            return;
        }

        $sequence = $target->selectOne(
            'SELECT pg_get_serial_sequence(?, ?) AS sequence',
            [$table, 'id'],
        )->sequence;

        if ($sequence === null) {
            return;
        }

        $maximumId = $target->table($table)->max('id');

        if ($maximumId !== null) {
            $target->selectOne(
                'SELECT setval(?::regclass, ?, true)',
                [$sequence, $maximumId],
            );
        }
    }
}
