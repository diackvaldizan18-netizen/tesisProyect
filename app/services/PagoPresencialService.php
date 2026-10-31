<?php
namespace App\Services;
use App\Models\Pago;
use Illuminate\Validation\ValidationException;

class PagoPresencialService {
    public function preparar(int $total,array $data,bool $referenciaObligatoria=true): array {
        $method=$data['metodo_pago']??'';
        if($total<=0 || !in_array($method,['efectivo','yape','plin'],true)) $this->error('metodo_pago','Selecciona un medio válido y un importe positivo.');
        $mixed=!empty($data['pago_combinado']);
        if($mixed && $method==='efectivo') $this->error('metodo_pago','Selecciona Yape o Plin para combinar con efectivo.');
        $digital=$method==='efectivo'?0:($mixed?LiquidacionService::centavos($data['monto_digital']??0):$total);
        if($mixed && ($digital<=0 || $digital>=$total)) $this->error('monto_digital','La parte digital debe ser mayor a cero y menor al total.');
        $cash=$total-$digital;
        $received=$cash>0?LiquidacionService::centavos($data['efectivo_recibido']??0):null;
        if($cash>0 && $received<$cash) $this->error('efectivo_recibido','El efectivo recibido no cubre la parte pendiente.');
        $reference=null;$key=null;
        if($digital>0 && $referenciaObligatoria){
            $reference=strtoupper(trim($data['referencia_pago']??''));
            if(!preg_match('/\A[A-Z0-9-]{1,80}\z/',$reference)) $this->error('referencia_pago','Registra la operación verificada en la cuenta del negocio.');
            $key=$method.':'.$reference;
            if(Pago::where('operacion_unica',$key)->exists()) $this->error('referencia_pago','Esta operación ya se utilizó.');
        }
        $parts=[];if($cash>0)$parts['efectivo']=$cash;if($digital>0)$parts[$method]=$digital;
        return ['metodo_pago'=>$method,'desglose'=>$parts,'efectivo_entregado_centavos'=>$received,'vuelto_centavos'=>$received===null?null:$received-$cash,'referencia_pago'=>$reference,'operacion_unica'=>$key];
    }
    private function error(string $field,string $message): never {throw ValidationException::withMessages([$field=>$message]);}
}
