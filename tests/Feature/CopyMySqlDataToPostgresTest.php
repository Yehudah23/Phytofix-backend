<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CopyMySqlDataToPostgresTest extends TestCase
{
    public function test_copies_data_once_and_preserves_source_records(): void
    {
        $this->configureDatabaseConnections();

        DB::connection('mysql_source')->table('products')->insert([
            ['id' => 5, 'name' => 'Tea'],
            ['id' => 8, 'name' => 'Tincture'],
        ]);

        $this->artisan('database:copy-mysql-to-postgres', ['--force' => true])
            ->assertExitCode(0);

        $this->assertSame(
            ['Tea', 'Tincture'],
            DB::connection('pgsql')->table('products')->orderBy('id')->pluck('name')->all(),
        );
        $this->assertSame(2, DB::connection('mysql_source')->table('products')->count());
        $this->assertSame(
            'mysql_to_postgres',
            DB::connection('pgsql')->table('database_imports')->value('name'),
        );

        $this->artisan('database:copy-mysql-to-postgres', ['--force' => true])
            ->assertExitCode(0);

        $this->assertSame(2, DB::connection('pgsql')->table('products')->count());
    }

    public function test_refuses_to_copy_into_a_non_empty_target_table(): void
    {
        $this->configureDatabaseConnections();

        DB::connection('mysql_source')->table('products')->insert([
            'id' => 5,
            'name' => 'Tea',
        ]);
        DB::connection('pgsql')->table('products')->insert([
            'id' => 1,
            'name' => 'Existing product',
        ]);

        $this->artisan('database:copy-mysql-to-postgres', ['--force' => true])
            ->assertExitCode(1);

        $this->assertSame(
            ['Existing product'],
            DB::connection('pgsql')->table('products')->pluck('name')->all(),
        );
        $this->assertSame(1, DB::connection('mysql_source')->table('products')->count());
        $this->assertSame(0, DB::connection('pgsql')->table('database_imports')->count());
    }

    public function test_refuses_to_copy_when_the_source_has_an_unhandled_table(): void
    {
        $this->configureDatabaseConnections();
        Schema::connection('mysql_source')->create('unhandled_data', function (Blueprint $table): void {
            $table->id();
        });

        $this->artisan('database:copy-mysql-to-postgres', ['--force' => true])
            ->assertExitCode(1);

        $this->assertSame(0, DB::connection('pgsql')->table('products')->count());
        $this->assertSame(0, DB::connection('pgsql')->table('database_imports')->count());
    }

    private function configureDatabaseConnections(): void
    {
        $connection = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ];

        config([
            'database.connections.mysql_source' => $connection,
            'database.connections.pgsql' => $connection,
        ]);

        DB::purge('mysql_source');
        DB::purge('pgsql');

        Schema::connection('mysql_source')->create('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
        });

        Schema::connection('pgsql')->create('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
        });

        Schema::connection('pgsql')->create('database_imports', function (Blueprint $table): void {
            $table->string('name')->primary();
            $table->json('row_counts');
            $table->timestamp('completed_at');
        });
    }
}
