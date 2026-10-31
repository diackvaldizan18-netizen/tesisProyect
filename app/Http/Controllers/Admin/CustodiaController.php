<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Estadia;
use App\Services\CustodiaService;
use Illuminate\Http\Request;

class CustodiaController extends Controller {
    public function store(Request $request, Estadia $estadia, CustodiaService $service) {
        $data = $request->validate(['accion'=>'required|in:recibir,retirar,guardar,devolver',
            'casillero'=>'nullable|required_if:accion,recibir|integer|between:1,41','confirmado'=>'required|accepted']);
        $service->registrar($estadia,$data,$request->user());
        return back()->with('success','Custodia de llaves actualizada.');
    }
}
