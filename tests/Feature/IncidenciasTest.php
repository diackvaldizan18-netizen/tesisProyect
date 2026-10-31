<?php
namespace Tests\Feature;
use App\Models\{User,Incidencia};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
class IncidenciasTest extends TestCase {
    use RefreshDatabase;
    public function test_audited_progression_escalation_private_evidence_and_stale_update():void {
        Storage::fake('local');$staff=User::factory()->create(['role'=>'operador']);
        $this->actingAs($staff)->get(route('admin.incidencias.index'))->assertOk();
        $data=['tipo'=>'llave_ticket','descripcion'=>'Cliente informa que perdió su ticket.','prioridad'=>'alta','responsable_id'=>$staff->id,'placa'=>'ABC-123'];
        $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->post(route('admin.incidencias.store'),$data+['evidencias'=>[UploadedFile::fake()->createWithContent('evidencia.png',$png)]])->assertSessionHasNoErrors();
        $item=Incidencia::firstOrFail();$this->assertTrue($item->escalada);$this->assertSame('ABC123',$item->placa);
        $this->get(route('admin.incidencias.show',$item))->assertOk()->assertSee('Cliente informa');
        $file=DB::table('incidencia_evidencias')->first();Storage::disk('local')->assertExists($file->ruta);
        $this->get(route('admin.incidencias.evidencia',[$item,$file->id]))->assertOk();
        $update=['version'=>1,'estado'=>'cerrada','prioridad'=>'media','responsable_id'=>$staff->id,'nota'=>'Revisamos el caso'];
        $this->post(route('admin.incidencias.update',$item),$update)->assertSessionHasErrors('estado');
        $update['estado']='en_atencion';$this->post(route('admin.incidencias.update',$item),$update)->assertSessionHasNoErrors();
        $this->post(route('admin.incidencias.update',$item),$update)->assertSessionHasErrors('version');
        $update['version']=2;$update['estado']='resuelta';$this->post(route('admin.incidencias.update',$item),$update)->assertSessionHasNoErrors();
        $update['version']=3;$update['estado']='cerrada';$this->post(route('admin.incidencias.update',$item),$update)->assertSessionHasNoErrors();
        $this->assertSame(4,DB::table('incidencia_eventos')->count());$this->assertSame('cerrada',$item->fresh()->estado);
        $this->actingAs(User::factory()->create(['role'=>'user']))->get(route('admin.incidencias.show',$item))->assertForbidden();
        $this->get(route('admin.incidencias.evidencia',[$item,$file->id]))->assertForbidden();
        $this->post(route('admin.incidencias.store'),$data)->assertForbidden();
    }
    public function test_invalid_ticket_and_executable_upload_are_rejected():void {
        $staff=User::factory()->create(['role'=>'operador']);$this->actingAs($staff);
        $data=['tipo'=>'otro','descripcion'=>'Descripción de la novedad','prioridad'=>'media','responsable_id'=>$staff->id];
        $this->post(route('admin.incidencias.store'),$data+['ticket'=>'INEXISTENTE'])->assertSessionHasErrors('ticket');
        $this->post(route('admin.incidencias.store'),$data+['evidencias'=>[UploadedFile::fake()->createWithContent('x.php','<?php echo 1;')]])->assertSessionHasErrors('evidencias.0');
        $this->assertSame(0,Incidencia::count());
    }
}
