<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Incidencia,Estadia,Espacio,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Validation\{Rule,ValidationException};

class IncidenciaController extends Controller {
    private function personal(){return User::where('activo',true)->whereIn('role',['admin','operador','super_admin'])->orderBy('name')->get();}
    public function index(Request $r){
        $r->validate(['estado'=>['nullable',Rule::in(array_merge(['pendientes'],array_keys(Incidencia::ESTADOS)))],'buscar'=>'nullable|string|max:80','escaladas'=>'nullable|in:1']);
        $q=Incidencia::with('responsable','espacio');
        if($r->input('estado','pendientes')==='pendientes') $q->whereIn('estado',['abierta','en_atencion']);
        elseif($r->filled('estado')) $q->where('estado',$r->estado);
        if($r->boolean('escaladas')) $q->where('escalada',true);
        if($r->filled('buscar')) $q->where(fn($q)=>$q->where('placa','like','%'.strtoupper($r->buscar).'%')->orWhere('descripcion','like','%'.$r->buscar.'%')->orWhereHas('estadia',fn($q)=>$q->where('codigo_ticket','like','%'.$r->buscar.'%')));
        return view('admin.incidencias.index',['incidencias'=>$q->latest()->paginate(15)->withQueryString(),'personal'=>$this->personal(),'espacios'=>Espacio::orderBy('codigo')->get()]);
    }
    private function datos(Request $r,bool $update):array {
        return $r->validate(array_merge([
            'prioridad'=>['required',Rule::in(array_keys(Incidencia::PRIORIDADES))],
            'responsable_id'=>['required',Rule::exists('users','id')->where(fn($q)=>$q->where('activo',true)->whereIn('role',['admin','operador','super_admin']))],
            'escalada'=>'sometimes|accepted','evidencias'=>'nullable|array|max:3','evidencias.*'=>'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ],$update?['estado'=>['required',Rule::in(array_keys(Incidencia::ESTADOS))],'nota'=>'required|string|min:5|max:3000','version'=>'required|integer|min:1']:[
            'tipo'=>['required',Rule::in(array_keys(Incidencia::TIPOS))],'descripcion'=>'required|string|min:10|max:5000',
            'placa'=>'nullable|string|regex:/\A[A-Z0-9]{5,10}\z/','ticket'=>'nullable|string|max:50','espacio_id'=>'nullable|exists:espacios,id',
        ]));
    }
    public function store(Request $r){
        $r->merge(['placa'=>strtoupper(preg_replace('/[\s-]+/','',(string)$r->placa)) ?: null]);
        $data=$this->datos($r,false);$paths=[];
        try{$item=DB::transaction(function()use($r,$data,&$paths){
            $stay=empty($data['ticket'])?null:Estadia::where('codigo_ticket',$data['ticket'])->first();
            if(!empty($data['ticket'])&&!$stay) throw ValidationException::withMessages(['ticket'=>'No se encontró ese ticket.']);
            if($stay && ((!empty($data['placa'])&&$data['placa']!==$stay->placa)||(!empty($data['espacio_id'])&&(int)$data['espacio_id']!==$stay->espacio_id))) throw ValidationException::withMessages(['ticket'=>'La placa o espacio no corresponden al ticket. Déjalos vacíos para usar sus datos.']);
            $item=Incidencia::create(['tipo'=>$data['tipo'],'descripcion'=>$data['descripcion'],'prioridad'=>$data['prioridad'],'estado'=>'abierta',
                'responsable_id'=>$data['responsable_id'],'registrado_por'=>$r->user()->id,'escalada'=>$r->boolean('escalada')||in_array($data['prioridad'],['alta','critica']),
                'estadia_id'=>$stay?->id,'placa'=>$stay?->placa??($data['placa']??null),'espacio_id'=>$stay?->espacio_id??($data['espacio_id']??null)]);
            $this->evento($item,$r,'Incidencia registrada.',['estado'=>'abierta','prioridad'=>$item->prioridad,'responsable_id'=>$item->responsable_id,'escalada'=>$item->escalada]);
            $this->guardarArchivos($item,$r,$paths);return $item;
        });}catch(\Throwable $e){foreach($paths as $path) Storage::disk('local')->delete($path);throw $e;}
        return redirect()->route('admin.incidencias.show',$item)->with('success','Incidencia registrada.');
    }
    public function show(Incidencia $incidencia){
        return response()->view('admin.incidencias.show',['incidencia'=>$incidencia->load('responsable','autor','estadia','espacio'),'personal'=>$this->personal(),
            'eventos'=>DB::table('incidencia_eventos')->join('users','users.id','=','incidencia_eventos.operador_id')->where('incidencia_id',$incidencia->id)->select('incidencia_eventos.*','users.name')->orderByDesc('incidencia_eventos.id')->get(),
            'evidencias'=>DB::table('incidencia_evidencias')->where('incidencia_id',$incidencia->id)->get()])->header('Cache-Control','private, no-store');
    }
    public function update(Request $r,Incidencia $incidencia){
        $data=$this->datos($r,true);$paths=[];
        try{DB::transaction(function()use($r,$incidencia,$data,&$paths){
            $item=Incidencia::lockForUpdate()->findOrFail($incidencia->id);
            if($item->version!==(int)$data['version']) throw ValidationException::withMessages(['version'=>'Otra persona actualizó la incidencia. Recarga antes de guardar.']);
            $allowed=['abierta'=>['abierta','en_atencion','resuelta'],'en_atencion'=>['en_atencion','resuelta'],'resuelta'=>['resuelta','en_atencion','cerrada'],'cerrada'=>['en_atencion']];
            if(!in_array($data['estado'],$allowed[$item->estado])) throw ValidationException::withMessages(['estado'=>'Primero atiende y resuelve la incidencia antes de cerrarla. Una cerrada puede reabrirse en atención.']);
            $changes=['estado'=>$data['estado'],'prioridad'=>$data['prioridad'],'responsable_id'=>(int)$data['responsable_id'],'escalada'=>$item->escalada||$r->boolean('escalada')||in_array($data['prioridad'],['alta','critica'])];
            $before=$item->only(array_keys($changes));$item->update($changes+['version'=>$item->version+1]);
            $this->evento($item,$r,$data['nota'],['antes'=>$before,'despues'=>$changes]);$this->guardarArchivos($item,$r,$paths);
        });}catch(\Throwable $e){foreach($paths as $path) Storage::disk('local')->delete($path);throw $e;}
        return back()->with('success','Seguimiento registrado.');
    }
    private function evento(Incidencia $item,Request $r,string $nota,array $cambios):void {
        DB::table('incidencia_eventos')->insert(['incidencia_id'=>$item->id,'operador_id'=>$r->user()->id,'nota'=>$nota,'cambios'=>json_encode($cambios),'created_at'=>now()]);
    }
    private function guardarArchivos(Incidencia $item,Request $r,array &$paths):void {
        foreach($r->file('evidencias',[]) as $file){
            $path=$file->store('incidencias/'.$item->id,'local');if(!$path) throw new \RuntimeException('No se pudo guardar la evidencia.');$paths[]=$path;
            DB::table('incidencia_evidencias')->insert(['incidencia_id'=>$item->id,'ruta'=>$path,'mime'=>$file->getMimeType(),'created_at'=>now()]);
        }
    }
    public function evidencia(Incidencia $incidencia,int $evidencia){
        $file=DB::table('incidencia_evidencias')->where('incidencia_id',$incidencia->id)->where('id',$evidencia)->first();abort_unless($file && Storage::disk('local')->exists($file->ruta),404);
        return Storage::disk('local')->download($file->ruta,'evidencia-'.$file->id.'.'.pathinfo($file->ruta,PATHINFO_EXTENSION),['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
}
