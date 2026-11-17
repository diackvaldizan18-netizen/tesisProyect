<?php

// Opt-in integration check. Creates two new local schemas and never drops them.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $e): void {
    fwrite(STDERR, get_class($e).': '.$e->getMessage().PHP_EOL);
    exit(1);
});
$config = config('database.connections.mysql');
if (! $app->environment('local') || ! in_array($config['host'], ['localhost', '127.0.0.1'], true)
    || ! empty($config['url']) || ! empty($config['read']) || ! empty($config['write'])
    || ($argv[1] ?? '') !== '--create-test-schemas') {
    throw new RuntimeException('Solo para MySQL local. Requiere --create-test-schemas.');
}

function check(bool $ok, string $message): void
{
    if (! $ok) {
        throw new RuntimeException($message);
    }
}

function migrateTestSchema(array $paths = []): void
{
    $status = Artisan::call('migrate', ['--database' => 'recovery_test', '--force' => true, '--path' => $paths]);
    check($status === 0, Artisan::output());
}

$files = glob(database_path('migrations/*.php'));
$checkout = database_path('migrations/2026_11_07_000001_checkout_payments.php');
$position = array_search(basename($checkout), array_map('basename', $files), true);
check($position !== false, 'No se encontró checkout en el historial');
$prefix = array_slice($files, 0, $position);
$suffix = bin2hex(random_bytes(5));
foreach (['fresh', 'partial'] as $scenario) {
    $database = "parkeo_qa_{$scenario}_{$suffix}";
    DB::connection('mysql')->statement("CREATE DATABASE `$database` CHARACTER SET utf8mb4");
    config(['database.connections.recovery_test' => array_replace($config, ['database' => $database])]);
    DB::purge('recovery_test');
    DB::setDefaultConnection('recovery_test');
    check(DB::selectOne('select database() as db')->db === $database, 'Conexión de prueba incorrecta');
    echo "$scenario: $database\n";

    if ($scenario === 'partial') {
        migrateTestSchema(array_map(fn ($f) => 'database/migrations/'.basename($f), $prefix));
        check(DB::table('migrations')->count() === count($prefix), 'Historial anterior incompleto');
        Schema::create('clientes_cobro', function (Blueprint $t) {
            $t->id();
            $t->string('nombres', 100);
            $t->string('apellidos', 100);
            $t->string('celular', 15)->nullable();
            $t->string('tipo_documento', 3);
            $t->string('numero_documento', 11); // UNIQUE still pending.
            $t->timestamps();
        });
        DB::table('clientes_cobro')->insert(['nombres' => 'Prueba', 'apellidos' => 'Local', 'tipo_documento' => 'DNI', 'numero_documento' => '00000001']);
        Schema::table('estadias', function (Blueprint $t) {
            $t->foreignId('cliente_cobro_id')->nullable(); // FK still pending.
            $t->string('tipo_comprobante', 7)->nullable();
            $t->string('tipo_impresion', 12)->nullable();
            $t->decimal('descuento_porcentaje', 5, 2)->default(0);
            $t->decimal('descuento_monto', 10, 2)->default(0);
        });
        Schema::table('pagos', fn (Blueprint $t) => $t->index('estadia_id', 'qa_estadia_lookup'));
        Schema::table('pagos', fn (Blueprint $t) => $t->dropUnique('pagos_estadia_id_unique'));
    }
    migrateTestSchema();
    migrateTestSchema();
    check(DB::table('migrations')->count() === count($files), 'Historial final incompleto');
    foreach (array_slice($files, count($prefix)) as $file) {
        (require $file)->up(); // Replay even if DDL completed just before journal insertion.
    }
    check(Schema::hasColumn('estadias', 'motivo_descuento'), 'Falta motivo_descuento');
    check(Schema::hasColumns('pagos', ['efectivo_recibido', 'vuelto', 'reportable_historico']), 'Faltan columnas de pago');
    check(Schema::hasColumns('configuracion_pagos', ['razon_social', 'ruc', 'direccion_fiscal']), 'Faltan datos fiscales');
    check(Schema::hasIndex('clientes_cobro', ['numero_documento'], 'unique'), 'Falta unicidad de documento');
    foreach (['estadia_id' => 'estadias', 'abono_id' => 'abonos'] as $col => $parent) {
        check(! Schema::hasIndex('pagos', [$col], 'unique'), "Sigue UNIQUE en $col");
        check(collect(Schema::getForeignKeys('pagos'))->contains(fn ($fk) => $fk['columns'] === [$col] && $fk['foreign_table'] === $parent), "Falta FK $col");
    }
    check(collect(Schema::getForeignKeys('estadias'))->contains(fn ($fk) => $fk['columns'] === ['cliente_cobro_id'] && $fk['foreign_table'] === 'clientes_cobro'), 'Falta FK a clientes_cobro');
    if ($scenario === 'partial') {
        check(DB::table('clientes_cobro')->where('numero_documento', '00000001')->count() === 1, 'No se conservó la fila');
        Schema::table('clientes_cobro', fn (Blueprint $t) => $t->string('celular', 20)->nullable()->change());
        try {
            (require $checkout)->up();
            throw new LogicException('No se detectó el esquema incompatible');
        } catch (RuntimeException $e) {
            check(str_contains($e->getMessage(), 'clientes_cobro.celular'), $e->getMessage());
        }
    }
    echo count($files)." migraciones y comprobaciones correctas\n";
}
