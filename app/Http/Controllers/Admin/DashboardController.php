<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Espacio, Estadia, Pago, Reserva};
use App\Services\{ReservaDisponibilidadService, ReservaService};

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request, ReservaService $reservas, ReservaDisponibilidadService $disponibilidad)
    {
        $filters = $request->validate(['reubicar'=>'nullable|integer|min:1','periodo'=>'nullable|in:dia,mes', 'fecha'=>'nullable|date_format:Y-m-d|before_or_equal:today']);
        $monthly = $request->user()->esSuperAdmin() && ($filters['periodo'] ?? 'dia') === 'mes';
        $date = $request->user()->esSuperAdmin() && !empty($filters['fecha']) ? \Carbon\Carbon::parse($filters['fecha'], config('app.timezone')) : now();
        $summary = app(\App\Services\DashboardSummaryService::class)->summarize($date, $monthly);
        $reservas->procesarReservasAutomaticas();
        $espacios = Espacio::conEstadoOperativo()->with(['sensor','vehiculoTipos.tarifas','abonoVigente.abonado',
            'estadias' => fn ($q) => $q->with(['reserva.usuario','vehiculoTipo'])->activas(),
            'reservas' => fn ($q) => $q->with(['usuario', 'pagos'])->whereIn('estado',['pendiente_pago','confirmada'])->whereNull('inasistencia_at')->whereDoesntHave('estadia'),
        ])->where('activo',true)->orderBy('codigo')->get();
        $disponibilidadPorEspacio = $espacios->mapWithKeys(fn ($e) => [$e->id => $disponibilidad->estadoParaTarjeta($e)]);
        $libres = $espacios->where('estado_actual','libre')->count();
        $activas = Estadia::activas()->count();
        $pendientes = Pago::where('estado','pendiente')->whereNotNull('enviado_at')->count();
        $cobros = Pago::where('estado','aprobado')->whereDate('pagado_at',today())->sum('monto');
        $llavesPendientes = Estadia::activas()->where('llave_recibida',false)->where(fn ($q)=>$q->whereNull('custodia_estado')->orWhere('custodia_estado','pendiente'))->count();
        $libresGenerales = $espacios->where('estado_actual','libre')->where('tiene_abono_vigente',false)->count();
        $libresAbonados = $libres - $libresGenerales;
        $entradasHoy = Estadia::whereDate('hora_ingreso',today())->count();
        $salidasHoy = Estadia::whereDate('hora_salida',today())->count();
        $llegadas = Reserva::with('usuario','espacio')->where(fn ($q)=>$q->where('estado','confirmada')->orWhere(fn ($q)=>$q->where('pago_al_llegar',true)->where('estado','pendiente_pago')->where('expires_at','>=',now())))->whereDoesntHave('estadia')->whereNull('inasistencia_at')->orderBy('fecha_reserva')->orderBy('hora_inicio')->limit(5)->get();
        $incidenciasPendientes = \App\Models\Incidencia::whereIn('estado',['abierta','en_atencion'])->count();
        $incidenciasEscaladas = \App\Models\Incidencia::whereIn('estado',['abierta','en_atencion'])->where('escalada',true)->count();
        $salidasPendientes = Estadia::activas()->whereNotNull('pago_registrado_at')->count();
        $cajaActiva = \App\Models\CajaTurno::with('responsable')->where('caja_activa',1)->first();
        $moverTicket = $request->filled('reubicar') ? Estadia::with('espacio')->activas()->whereNull('abono_id')->whereHas('espacio.abonoVigente')->findOrFail($request->integer('reubicar')) : null;
        $moverDestinos = $moverTicket ? $espacios->filter(fn($e)=>$e->id!==$moverTicket->espacio_id && $e->estado_actual==='libre' && !$e->tiene_abono_vigente && $e->vehiculoTipos->contains(fn($t)=>$t->id===$moverTicket->vehiculo_tipo_id && $t->activo) && ($e->modo_monitoreo!=='sensor' || ($e->sensor?->integracion_iot && $e->sensor?->lectura_vigente))) : collect();
        $ownerOverview = null;
        if ($request->user()->esSuperAdmin()) {
            $ownerOverview = [
                'caja' => $cajaActiva ? app(\App\Services\CajaService::class)->resumen($cajaActiva) : null,
                'cierres' => \App\Models\CajaTurno::with('responsable')->whereNotNull('cerrada_at')->orderByDesc('cerrada_at')->orderByDesc('id')->limit(3)->get(),
                'incidencias' => \App\Models\Incidencia::whereIn('estado',['abierta','en_atencion'])->where('escalada',true)->latest('id')->limit(3)->get(),
            ];
        }
        return response()->view('admin.dashboard',compact('moverTicket','moverDestinos','ownerOverview','incidenciasPendientes','incidenciasEscaladas','salidasPendientes','cajaActiva','llavesPendientes','libresGenerales','libresAbonados','entradasHoy','salidasHoy','espacios','disponibilidadPorEspacio','libres','activas','pendientes','cobros','llegadas','summary','monthly'))->header('Cache-Control','no-store');
    }
}
