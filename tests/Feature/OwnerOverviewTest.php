<?php
namespace Tests\Feature;

use App\Models\{Incidencia, Pago, User};
use App\Services\CajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OwnerOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_real_shift_totals_closures_and_only_open_escalations(): void
    {
        $owner = User::factory()->create(['role'=>'super_admin']);
        $staff = User::factory()->create(['role'=>'operador']);
        $service = app(CajaService::class);
        $closed = $service->abrir(['fondo'=>50],$staff);
        $service->cerrar($closed,['contado'=>45,'observaciones'=>'Faltante pendiente de revisar'],$staff);
        $service->abrir(['fondo'=>100],$staff);
        DB::transaction(function () use ($service,$staff) {
            foreach (['efectivo'=>10,'yape'=>20,'plin'=>30] as $method=>$amount) {
                $payment = Pago::create(['codigo_pago'=>'OWNER-'.$method,'metodo_pago'=>$method,'monto'=>$amount,'estado'=>'aprobado','pagado_at'=>now()]);
                $service->registrarCobro($payment,$staff);
            }
        });
        foreach (['abierta'=>'ABC123','cerrada'=>'XYZ999'] as $state=>$plate) {
            Incidencia::create(['tipo'=>'cobro','descripcion'=>'Revisión de cobro','prioridad'=>'alta','estado'=>$state,'placa'=>$plate,'registrado_por'=>$staff->id,'responsable_id'=>$owner->id,'escalada'=>true]);
        }
        $this->actingAs($owner)->get('/admin?periodo=mes')->assertOk()
            ->assertSee('Supervisión del dueño')->assertSee('S/ 110.00')->assertSee('S/ 20.00')->assertSee('S/ 30.00')
            ->assertSee('Cierre calculado')->assertSee('ABC123')->assertDontSee('XYZ999')
            ->assertSee('Resumen mensual')->assertSee('reception-drawer',false)->assertSee('data-open-ticket-scanner',false)
            ->assertViewHas('ownerOverview', fn($data)=>$data['caja']['esperado']===11000 && $data['incidencias']->count()===1);
        $this->actingAs($staff)->get('/admin')->assertOk()->assertDontSee('Supervisión del dueño')
            ->assertViewHas('ownerOverview',null);
    }

    public function test_owner_empty_dashboard_has_no_invented_cash_or_closures(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'super_admin']))->get('/admin')->assertOk()
            ->assertSee('No hay turno abierto')->assertSee('No hay turnos cerrados registrados')
            ->assertSee('No hay incidencias escaladas pendientes');
    }
}
