<?php
namespace App\Services;
use App\Models\{CajaTurno,CajaMovimiento,Pago,User};
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CajaService {
    public function abrir(array $data, User $staff): CajaTurno {
        $this->staff($staff);
        try {
            return DB::transaction(function() use($data,$staff){
                if(CajaTurno::where('caja_activa',1)->exists()) $this->error('La caja ya tiene un turno abierto.');
                $fondo=LiquidacionService::centavos($data['fondo']);
                if($fondo<0) $this->error('El fondo no puede ser negativo.');
                return CajaTurno::create(['caja_activa'=>1,'responsable_id'=>$staff->id,'abierta_at'=>now(),'fondo_centavos'=>$fondo,'observaciones_apertura'=>$data['observaciones']??null]);
            });
        } catch(UniqueConstraintViolationException $e){ $this->error('Otro trabajador abrió la caja. Actualiza la pantalla.'); }
    }
    // Caller owns the payment transaction; the turn lock serializes collection against closing.
    public function registrarCobro(Pago $pago, User $staff): void {
        $this->staff($staff);
        if(DB::transactionLevel()<1) throw new \LogicException('El cobro de caja requiere una transacción.');
        if($pago->estado!=='aprobado') $this->error('Solo se registran pagos verificados.');
        $turno=CajaTurno::where('caja_activa',1)->lockForUpdate()->first();
        if(!$turno) $this->error('Abre la caja antes de registrar un cobro.');
        if(CajaMovimiento::where('pago_id',$pago->id)->exists()) return;
        $monto=LiquidacionService::centavos($pago->monto);
        if($monto<0 || !in_array($pago->metodo_pago,['efectivo','yape','plin'],true)) $this->error('El pago no tiene un importe o medio válido.');
        if($pago->desglose){
            if(array_diff(array_keys($pago->desglose),['efectivo','yape','plin']) || array_sum($pago->desglose)!==$monto || collect($pago->desglose)->contains(fn($n)=>!is_int($n)||$n<=0)) $this->error('El desglose del cobro no coincide con el importe.');
        }
        CajaMovimiento::create(['caja_turno_id'=>$turno->id,'operador_id'=>$staff->id,'pago_id'=>$pago->id,
            'tipo'=>'cobro','metodo'=>$pago->metodo_pago,'desglose'=>$pago->desglose,'monto_centavos'=>$monto,'created_at'=>now(),
            'motivo'=>$pago->abono_id?'Mensualidad de abonado':($pago->estadia_id?'Cobro de estadía':'Pago de reserva')]);
    }
    public function resumen(CajaTurno $turno): array {
        $res=['efectivo'=>0,'yape'=>0,'plin'=>0,'ingresos'=>0,'egresos'=>0];
        foreach($turno->movimientos()->get() as $row){
            if($row->tipo==='cobro') {
                foreach(($row->desglose ?: [$row->metodo=>(int)$row->monto_centavos]) as $method=>$amount) $res[$method]+=(int)$amount;
            } elseif($row->tipo==='ingreso') $res['ingresos']+=(int)$row->monto_centavos;
            elseif($row->tipo==='egreso') $res['egresos']+=(int)$row->monto_centavos;
        }
        $res['esperado']=$turno->fondo_centavos+$res['efectivo']+$res['ingresos']-$res['egresos'];
        return $res;
    }
    public function cerrar(CajaTurno $turno,array $data,User $staff): void {
        $this->staff($staff);
        DB::transaction(function() use($turno,$data,$staff){
            $turno=CajaTurno::lockForUpdate()->findOrFail($turno->id);
            abort_unless($turno->responsable_id===$staff->id || $staff->esSuperAdmin(),403);
            if(!$turno->caja_activa || $turno->cerrada_at) $this->error('El turno ya está cerrado.');
            $esperado=$this->resumen($turno)['esperado'];
            $turno->update(['caja_activa'=>null,'cerrada_at'=>now(),'cerrada_por'=>$staff->id,'esperado_centavos'=>$esperado,
                'contado_centavos'=>null,'diferencia_centavos'=>null,'observaciones_cierre'=>$data['observaciones']??null]);
        });
    }
    public function movimiento(CajaTurno $turno,array $data,User $staff): void {
        abort_unless($staff->esSuperAdmin(),403);
        DB::transaction(function() use($turno,$data,$staff){
            $turno=CajaTurno::lockForUpdate()->findOrFail($turno->id);
            if(!$turno->caja_activa) $this->error('No se pueden agregar movimientos a un turno cerrado.');
            if(CajaMovimiento::where('solicitud_id',$data['solicitud_id'])->exists()) $this->error('Ese movimiento ya fue registrado.');
            $monto=LiquidacionService::centavos($data['monto']);
            if($monto<=0 || !in_array($data['tipo'],['ingreso','egreso']) || !trim($data['motivo'])) $this->error('Indica tipo, importe positivo y motivo.');
            if($data['tipo']==='egreso' && $monto>$this->resumen($turno)['esperado']) $this->error('El egreso supera el efectivo esperado en caja.');
            CajaMovimiento::create(['caja_turno_id'=>$turno->id,'operador_id'=>$staff->id,'solicitud_id'=>$data['solicitud_id'],
                'tipo'=>$data['tipo'],'metodo'=>'efectivo','monto_centavos'=>$monto,'motivo'=>$data['motivo'],'created_at'=>now()]);
        });
    }
    private function staff(User $staff): void { abort_unless($staff->activo && in_array($staff->role,['operador','admin','super_admin']),403); }
    private function error(string $message): never { throw ValidationException::withMessages(['caja'=>$message]); }
}
