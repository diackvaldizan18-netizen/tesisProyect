<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Incidencia extends Model {
    protected $guarded=['id'];
    public const TIPOS=['reclamo'=>'Queja o reclamo','dano'=>'Daño al vehículo','espacio'=>'Problema con espacio','abonado'=>'Problema con abonado','llave_ticket'=>'Pérdida de llave o ticket','cobro'=>'Problema de cobro','equipo'=>'Fallo de equipo o sensor','otro'=>'Otra novedad'];
    public const ESTADOS=['abierta'=>'Abierta','en_atencion'=>'En atención','resuelta'=>'Resuelta','cerrada'=>'Cerrada'];
    public const PRIORIDADES=['baja'=>'Baja','media'=>'Media','alta'=>'Alta','critica'=>'Crítica'];
    protected function casts():array{return ['escalada'=>'boolean'];}
    public function responsable(){return $this->belongsTo(User::class,'responsable_id');}
    public function autor(){return $this->belongsTo(User::class,'registrado_por');}
    public function estadia(){return $this->belongsTo(Estadia::class);}
    public function espacio(){return $this->belongsTo(Espacio::class);}
}
