<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('estadias', function (Blueprint $table) {
            $table->unsignedSmallInteger('casillero_activo')->nullable()->unique();
            $table->string('custodia_estado')->nullable();
            $table->foreignId('custodia_responsable_id')->nullable()->constrained('users');
        });
        Schema::create('custodia_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estadia_id')->constrained('estadias');
            $table->foreignId('operador_id')->constrained('users');
            $table->string('accion');
            $table->unsignedSmallInteger('casillero')->nullable();
            $table->timestamp('created_at');
        });
    }
    public function down(): void {
        Schema::dropIfExists('custodia_eventos');
        Schema::table('estadias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('custodia_responsable_id');
            $table->dropUnique(['casillero_activo']);
            $table->dropColumn(['casillero_activo','custodia_estado']);
        });
    }
};
