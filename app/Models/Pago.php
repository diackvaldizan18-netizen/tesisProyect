<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'reserva_id', 'estadia_id', 'abono_id', 'desglose', 'efectivo_entregado_centavos', 'vuelto_centavos',
        'user_id',
        'codigo_pago',
        'metodo_pago',
        'monto',
        'estado',
        'referencia_pago',
        'comprobante', 'enviado_at', 'revisado_at', 'revisado_por', 'motivo_revision', 'operacion_unica',
        'pagado_at',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2', 'desglose'=>'array',
            'pagado_at' => 'datetime', 'enviado_at' => 'datetime', 'revisado_at' => 'datetime',
        ];
    }

    public function getMedioLabelAttribute(): string {
        return $this->desglose ? implode(' + ',array_map('ucfirst',array_keys($this->desglose))) : ucfirst($this->metodo_pago);
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function estaAprobado(): bool
    {
        return $this->estado === 'aprobado';
    }

    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }
}
