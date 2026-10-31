<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CajaTurno extends Model {
    public $timestamps=false;
    protected $guarded=['id'];
    protected function casts(): array { return ['abierta_at'=>'datetime','cerrada_at'=>'datetime']; }
    public function responsable(){ return $this->belongsTo(User::class,'responsable_id'); }
    public function cierreResponsable(){ return $this->belongsTo(User::class,'cerrada_por'); }
    public function movimientos(){ return $this->hasMany(CajaMovimiento::class); }
}
