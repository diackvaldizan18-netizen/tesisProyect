<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{CajaTurno,Pago};
use App\Services\CajaService;
use Illuminate\Http\Request;
class CajaController extends Controller {
    public function index(Request $request,CajaService $service){
        $request->validate(['turno'=>'nullable|integer|min:1']);
        $activa=CajaTurno::with('responsable')->where('caja_activa',1)->first();
        $turno=$request->filled('turno')?CajaTurno::with('responsable')->findOrFail($request->turno):$activa;
        return response()->view('admin.caja.index',['activa'=>$activa,'turno'=>$turno,
            'resumen'=>$turno?$service->resumen($turno):null,
            'movimientos'=>$turno?$turno->movimientos()->with('operador','pago')->latest('id')->paginate(25,['*'],'movimientos')->withQueryString():null,
            'turnos'=>CajaTurno::with('responsable')->latest('id')->paginate(10,['*'],'historial')->withQueryString(),
        ])->header('Cache-Control','private, no-store');
    }
    public function abrir(Request $request,CajaService $service){
        $data=$request->validate(['fondo'=>'required|numeric|min:0|max:999999|decimal:0,2','observaciones'=>'nullable|string|max:1000']);
        $service->abrir($data,$request->user()); return redirect()->route('admin.caja.index')->with('success','Caja abierta. Los nuevos cobros quedarán asociados a este turno.');
    }
    public function cerrar(Request $request,CajaTurno $turno,CajaService $service){
        $data=$request->validate(['contado'=>'prohibited','observaciones'=>'nullable|string|max:1000','confirmado'=>'required|accepted']);
        $service->cerrar($turno,$data,$request->user());return redirect()->route('admin.caja.index',['turno'=>$turno->id])->with('success','Turno cerrado. El historial se conserva.');
    }
    public function movimiento(Request $request,CajaTurno $turno,CajaService $service){
        $data=$request->validate(['tipo'=>'required|in:ingreso,egreso','monto'=>'required|numeric|min:0.01|max:999999|decimal:0,2','motivo'=>'required|string|min:5|max:1000','solicitud_id'=>'required|uuid']);
        $service->movimiento($turno,$data,$request->user());return back()->with('success','Movimiento de efectivo registrado.');
    }
}
