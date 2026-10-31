<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('pagos',function(Blueprint $t){$t->json('desglose')->nullable();$t->bigInteger('efectivo_entregado_centavos')->nullable();$t->bigInteger('vuelto_centavos')->nullable();});
        Schema::table('caja_movimientos',fn(Blueprint $t)=>$t->json('desglose')->nullable());
    }
    public function down(): void {
        Schema::table('caja_movimientos',fn(Blueprint $t)=>$t->dropColumn('desglose'));
        Schema::table('pagos',fn(Blueprint $t)=>$t->dropColumn(['desglose','efectivo_entregado_centavos','vuelto_centavos']));
    }
};
