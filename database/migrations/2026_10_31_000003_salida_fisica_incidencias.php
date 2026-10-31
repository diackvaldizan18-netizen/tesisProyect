<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('estadias',function(Blueprint $t){$t->timestamp('pago_registrado_at')->nullable();$t->foreignId('pago_registrado_por')->nullable()->constrained('users');});
        Schema::create('incidencias',function(Blueprint $t){
            $t->id();$t->string('tipo');$t->text('descripcion');$t->string('prioridad');$t->string('estado')->default('abierta');
            $t->string('placa',15)->nullable();$t->foreignId('estadia_id')->nullable()->constrained('estadias');$t->foreignId('espacio_id')->nullable()->constrained('espacios');
            $t->foreignId('registrado_por')->constrained('users');$t->foreignId('responsable_id')->constrained('users');
            $t->boolean('escalada')->default(false);$t->unsignedInteger('version')->default(1);$t->timestamps();
        });
        Schema::create('incidencia_eventos',function(Blueprint $t){
            $t->id();$t->foreignId('incidencia_id')->constrained('incidencias');$t->foreignId('operador_id')->constrained('users');
            $t->text('nota');$t->json('cambios');$t->timestamp('created_at');
        });
        Schema::create('incidencia_evidencias',function(Blueprint $t){
            $t->id();$t->foreignId('incidencia_id')->constrained('incidencias');$t->string('ruta');$t->string('mime');$t->timestamp('created_at');
        });
    }
    public function down(): void {
        Schema::dropIfExists('incidencia_evidencias');Schema::dropIfExists('incidencia_eventos');Schema::dropIfExists('incidencias');
        Schema::table('estadias',function(Blueprint $t){$t->dropConstrainedForeignId('pago_registrado_por');$t->dropColumn('pago_registrado_at');});
    }
};
