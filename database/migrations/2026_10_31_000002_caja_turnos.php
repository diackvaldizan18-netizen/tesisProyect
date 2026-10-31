<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('caja_turnos',function(Blueprint $t){
            $t->id(); $t->unsignedTinyInteger('caja_activa')->nullable()->unique();
            $t->foreignId('responsable_id')->constrained('users');
            $t->timestamp('abierta_at'); $t->bigInteger('fondo_centavos'); $t->text('observaciones_apertura')->nullable();
            $t->timestamp('cerrada_at')->nullable(); $t->foreignId('cerrada_por')->nullable()->constrained('users');
            $t->bigInteger('esperado_centavos')->nullable(); $t->bigInteger('contado_centavos')->nullable();
            $t->bigInteger('diferencia_centavos')->nullable(); $t->text('observaciones_cierre')->nullable();
        });
        Schema::create('caja_movimientos',function(Blueprint $t){
            $t->id(); $t->foreignId('caja_turno_id')->constrained('caja_turnos');
            $t->foreignId('operador_id')->constrained('users'); $t->foreignId('pago_id')->nullable()->unique()->constrained('pagos');
            $t->uuid('solicitud_id')->nullable()->unique(); $t->string('tipo'); $t->string('metodo');
            $t->bigInteger('monto_centavos'); $t->text('motivo'); $t->timestamp('created_at');
        });
    }
    public function down(): void { Schema::dropIfExists('caja_movimientos'); Schema::dropIfExists('caja_turnos'); }
};
