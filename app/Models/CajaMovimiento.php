<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CajaMovimiento extends Model {
    public $timestamps=false;
    protected $guarded=['id'];
    protected function casts(): array { return ['created_at'=>'datetime','desglose'=>'array']; }
    public function operador(){ return $this->belongsTo(User::class,'operador_id'); }
    public function pago(){ return $this->belongsTo(Pago::class); }
}
