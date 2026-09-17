<?php
namespace Tests\Feature;

use App\Models\{Machine, Reservation, User};
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MachineManagementTest extends TestCase
{
    use RefreshDatabase;
    private User $staff;
    private User $student;
    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-16 12:00:00'));
        $this->staff = User::factory()->create(['role'=>'personal']);
        $this->student = User::factory()->create();
        $this->machine = Machine::create($this->data() + ['type'=>'lavadora']);
    }

    private function data(): array
    {
        return ['name'=>'Lavadora prueba', 'capacity'=>8, 'location'=>'Planta baja', 'status'=>'disponible'];
    }

    private function booking(): Reservation
    {
        return app(BookingService::class)->book($this->student, $this->machine->id, Carbon::parse('2026-09-16 16:00'), 15);
    }

    public function test_staff_creates_edits_and_reenables_a_washer(): void
    {
        $this->actingAs($this->staff)->get('/maquinas/crear')->assertOk();
        $this->post('/maquinas', array_replace($this->data(), ['name'=>'Lavadora nueva']))->assertRedirect('/maquinas');
        $new = Machine::where('name', 'Lavadora nueva')->firstOrFail();
        $this->assertSame('lavadora', $new->type);
        $this->get(route('machines.edit', $new))->assertOk();
        $data = array_replace($this->data(), ['name'=>'Lavadora editada', 'capacity'=>12, 'location'=>'Segundo piso', 'status'=>'mantenimiento']);
        $this->patch(route('machines.update', $new), $data)->assertRedirect('/maquinas')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('machines', ['id'=>$new->id] + $data);
        $this->patch(route('machines.update', $new), array_replace($data, ['status'=>'disponible']))->assertSessionHasNoErrors();
        $this->assertSame('disponible', $new->fresh()->status);
    }

    public function test_student_and_guest_cannot_manage_machines(): void
    {
        $this->post('/maquinas', $this->data())->assertRedirect('/ingresar');
        $this->actingAs($this->student)->get('/maquinas/crear')->assertForbidden();
        $this->get(route('machines.edit', $this->machine))->assertForbidden();
        $this->post('/maquinas', $this->data())->assertForbidden();
        $this->patch(route('machines.update', $this->machine), $this->data())->assertForbidden();
        $this->delete(route('machines.destroy', $this->machine))->assertForbidden();
        $this->assertDatabaseCount('machines', 1);
    }

    public function test_invalid_machine_fields_and_duplicate_names_are_rejected(): void
    {
        $this->actingAs($this->staff)->post('/maquinas', ['name'=>$this->machine->name, 'capacity'=>0, 'location'=>'', 'status'=>'ocupada', 'type'=>'secadora'])
            ->assertSessionHasErrors(['name','capacity','location','status','type']);
        $this->assertDatabaseCount('machines', 1);
    }

    public function test_active_bookings_block_removal_and_maintenance(): void
    {
        $reservation = $this->booking();
        $this->actingAs($this->staff);
        foreach (['pendiente', 'en_proceso'] as $status) {
            $reservation->update(['status'=>$status]);
            $this->delete(route('machines.destroy', $this->machine))->assertSessionHasErrors('machine');
            $this->patch(route('machines.update', $this->machine), array_replace($this->data(), ['status'=>'mantenimiento']))->assertSessionHasErrors('machine');
            $this->assertNull($this->machine->fresh()->deleted_at);
            $this->assertSame('disponible', $this->machine->fresh()->status);
        }
    }

    public function test_deletion_preserves_history_and_prevents_new_bookings(): void
    {
        $reservation = $this->booking();
        $reservation->update(['status'=>'finalizada']);
        $this->actingAs($this->staff)->delete(route('machines.destroy', $this->machine))->assertRedirect('/maquinas')->assertSessionHasNoErrors();
        $this->assertSoftDeleted($this->machine);
        $this->assertSame($this->machine->name, $reservation->fresh()->machine->name);
        $this->actingAs($this->student)->get('/reservas')->assertSee($reservation->code)->assertSee($this->machine->name);
        $this->get('/reservar?date=2026-09-17&time=08:00')->assertDontSee($this->machine->name);
        $this->post('/reservas', ['machine_id'=>$this->machine->id,'date'=>'2026-09-17','time'=>'08:00','garment_count'=>3])->assertNotFound();
    }

    public function test_maintenance_is_independent_of_occupancy_and_can_be_removed(): void
    {
        $this->machine->update(['status'=>'mantenimiento']);
        $this->actingAs($this->student)->get('/reservar?date=2026-09-17&time=08:00')->assertSee('Mantenimiento')->assertDontSee('Disponible en este turno');
        $this->actingAs($this->staff)->patch(route('machines.update', $this->machine), $this->data())->assertSessionHasNoErrors();
        $this->actingAs($this->student)->get('/reservar?date=2026-09-17&time=08:00')->assertSee('Disponible en este turno');
    }

    public function test_past_slot_never_claims_to_be_available(): void
    {
        $this->actingAs($this->student)->get('/reservar?date=2026-09-16&time=08:00')
            ->assertOk()->assertSee('Horario iniciado')->assertDontSee('Disponible en este turno');
    }

    public function test_five_hours_after_slot_staff_can_start_then_finish_manually(): void
    {
        $reservation = $this->booking();
        $this->travelTo(Carbon::parse('2026-09-16 23:00:00'));
        $this->actingAs($this->staff)->get('/reservas')->assertSee('Sin inicio registrado')->assertSee('Seleccionar nuevo estado');
        $this->assertSame('pendiente', $reservation->fresh()->status);
        $this->patch(route('reservations.status', $reservation), ['status'=>'en_proceso'])->assertSessionHasNoErrors();
        $this->assertSame('en_proceso', $reservation->fresh()->status);
        $this->get('/reservas')->assertSee('Falta confirmar finalización');
        $this->travelTo(Carbon::parse('2026-09-17 05:00:00'));
        $this->assertSame('en_proceso', $reservation->fresh()->status);
        $this->patch(route('reservations.status', $reservation), ['status'=>'finalizada'])->assertSessionHasNoErrors();
        $this->actingAs($this->student)->get('/reservas')->assertSee('Finalizada')->assertDontSee('Falta confirmar finalización');
        $this->assertSame('finalizada', $reservation->fresh()->status);
    }
}
