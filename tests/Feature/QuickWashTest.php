<?php
namespace Tests\Feature;
use App\Models\{User, Machine, Reservation};
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuickWashTest extends TestCase
{
    use RefreshDatabase;
    private User $student;
    private User $staff;
    private Machine $machine;
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-15 07:00:00'));
        $this->student = User::factory()->create();
        $this->staff = User::factory()->create(['role' => 'personal']);
        $this->machine = Machine::create(['name' => 'Lavadora 01', 'capacity' => 8, 'type' => 'lavadora', 'location' => 'Planta baja', 'status' => 'disponible']);
    }
    private function payload(string $time = '10:00'): array { return ['machine_id' => $this->machine->id, 'date' => '2026-09-16', 'time' => $time, 'garment_count' => 8]; }
    private function book(string $time = '10:00'): Reservation { return app(BookingService::class)->book($this->student, $this->machine->id, Carbon::parse('2026-09-16 '.$time), 8); }

    public function test_registration_creates_student_and_ignores_role_escalation(): void
    {
        $this->post('/registro', ['name' => 'Ana', 'email' => 'ANA@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'role' => 'personal'])->assertRedirect('/inicio');
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com', 'role' => 'estudiante']);
        $this->assertAuthenticated();
    }
    public function test_registration_rejects_duplicate_email_and_unconfirmed_password(): void
    {
        $this->post('/registro', ['name'=>'Ana', 'email'=>$this->student->email, 'password'=>'Password123!', 'password_confirmation'=>'diferente'])->assertSessionHasErrors(['email', 'password']);
        $this->assertDatabaseCount('users', 2);
    }
    public function test_login_logout_and_invalid_credentials(): void
    {
        $this->post('/ingresar', ['email'=>$this->student->email, 'password'=>'bad'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/ingresar', ['email'=>$this->student->email, 'password'=>'password'])->assertRedirect('/inicio');
        $this->assertAuthenticatedAs($this->student);
        $this->post('/salir')->assertRedirect('/ingresar');
        $this->assertGuest();
        $this->get('/reservas')->assertRedirect('/ingresar');
    }
    public function test_repeated_login_failures_are_throttled(): void
    {
        foreach (range(1, 6) as $attempt) $this->post('/ingresar', ['email'=>'none@example.com','password'=>'bad'])->assertSessionHasErrors();
        $this->post('/ingresar', ['email'=>'none@example.com','password'=>'bad'])->assertStatus(429);
    }
    public function test_guest_and_role_boundaries(): void
    {
        $this->get('/reservas')->assertRedirect('/ingresar');
        $this->actingAs($this->student)->get('/maquinas')->assertForbidden();
        $this->actingAs($this->staff)->get('/reservar')->assertForbidden();
        $this->post('/reservas', $this->payload())->assertForbidden();
    }
    public function test_create_reservation_persists_machine_date_time_and_prendas(): void
    {
        $this->actingAs($this->student)->post('/reservas', $this->payload())->assertRedirect('/reservas');
        $this->assertDatabaseHas('reservations', ['user_id'=>$this->student->id, 'machine_id'=>$this->machine->id, 'starts_at'=>'2026-09-16 10:00:00', 'ends_at'=>'2026-09-16 11:00:00', 'garment_count'=>8, 'status'=>'pendiente']);
        $this->get('/reservas')->assertSee('QW-0001')->assertSee('Prendas');
    }
    public static function invalidQuantities(): array { return [[null], [0], [-1], [101], ['1.5'], ['muchas']]; }
    #[DataProvider('invalidQuantities')]
    public function test_invalid_garment_quantities_are_rejected(mixed $quantity): void
    {
        $this->actingAs($this->student)->post('/reservas', array_replace($this->payload(), ['garment_count'=>$quantity]))->assertSessionHasErrors('garment_count');
        $this->assertDatabaseCount('reservations', 0);
    }
    public function test_one_machine_cannot_be_booked_twice_and_adjacent_slot_is_allowed(): void
    {
        $this->actingAs($this->student)->post('/reservas', $this->payload())->assertRedirect('/reservas');
        $other = User::factory()->create();
        $this->actingAs($other)->post('/reservas', $this->payload())->assertSessionHasErrors('slot');
        $this->post('/reservas', $this->payload('11:00'))->assertRedirect('/reservas');
        $this->assertDatabaseCount('reservations', 2);
        $this->assertDatabaseCount('reservation_slots', 2);
    }
    public function test_different_machines_can_be_reserved_at_same_time(): void
    {
        $this->book();
        $otherMachine = $this->machine->replicate();
        $otherMachine->name = 'Lavadora 02'; $otherMachine->save();
        $this->actingAs(User::factory()->create())->post('/reservas', array_replace($this->payload(), ['machine_id'=>$otherMachine->id]))->assertRedirect('/reservas');
    }
    public function test_student_is_limited_to_three_active_reservations(): void
    {
        $first = $this->book('08:00'); $first->update(['status'=>'en_proceso']);
        $this->book('09:00'); $this->book('10:00');
        $this->actingAs($this->student)->post('/reservas', $this->payload('11:00'))->assertSessionHasErrors('slot');
        $this->assertDatabaseCount('reservations', 3);
    }
    public function test_finalized_and_cancelled_reservations_do_not_consume_quota(): void
    {
        $this->book('08:00')->update(['status'=>'finalizada']);
        $reservation = $this->book('09:00');
        app(BookingService::class)->transition($reservation, $this->student, 'cancelada');
        foreach (['10:00','11:00','12:00'] as $time) $this->actingAs($this->student)->post('/reservas', $this->payload($time))->assertRedirect('/reservas');
        $this->assertDatabaseCount('reservations', 5);
    }
    public function test_cancellation_releases_slot_for_another_student(): void
    {
        $reservation = $this->book();
        $this->actingAs($this->student)->patch(route('reservations.cancel', $reservation))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservations', ['id'=>$reservation->id, 'status'=>'cancelada']);
        $this->actingAs(User::factory()->create())->post('/reservas', $this->payload())->assertRedirect('/reservas');
        $this->assertDatabaseCount('reservation_slots', 1);
    }
    public static function cancellationTimes(): array { return [['09:59:59', true], ['10:00:00', false], ['10:00:01', false]]; }
    #[DataProvider('cancellationTimes')]
    public function test_cancellation_boundary_at_start_time(string $time, bool $allowed): void
    {
        $reservation = $this->book();
        $this->travelTo(Carbon::parse('2026-09-16 '.$time));
        $response = $this->actingAs($this->student)->patch(route('reservations.cancel', $reservation));
        if ($allowed) { $response->assertRedirect()->assertSessionHasNoErrors(); }
        else { $response->assertSessionHasErrors('status'); }
        $this->assertSame($allowed ? 'cancelada' : 'pendiente', $reservation->fresh()->status);
    }
    public function test_student_cannot_cancel_others_or_change_any_status(): void
    {
        $reservation = $this->book();
        $this->actingAs(User::factory()->create())->patch(route('reservations.cancel', $reservation))->assertForbidden();
        $this->get('/reservas?search=Lavadora')->assertDontSee($reservation->code);
        $this->actingAs($this->student)->patch(route('reservations.status', $reservation), ['status'=>'finalizada'])->assertForbidden();
    }
    public function test_only_pending_reservations_can_be_cancelled(): void
    {
        $reservation = $this->book();
        foreach (['en_proceso', 'finalizada', 'cancelada'] as $status) {
            $reservation->update(['status'=>$status]);
            $this->actingAs($this->student)->patch(route('reservations.cancel', $reservation))->assertSessionHasErrors('status');
            $this->assertSame($status, $reservation->fresh()->status);
        }
    }
    public function test_staff_completes_lifecycle_and_student_sees_finalized(): void
    {
        $reservation = $this->book();
        $this->travelTo(Carbon::parse('2026-09-16 10:00'));
        foreach (['en_proceso', 'finalizada'] as $status) {
            $this->actingAs($this->staff)->patch(route('reservations.status', $reservation), ['status'=>$status])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($status, $reservation->fresh()->status);
            $this->actingAs($this->student)->get('/reservas')->assertOk()->assertSee(Reservation::LABELS[$status]);
        }
    }
    public function test_staff_cannot_modify_student_machine_schedule_or_quantity(): void
    {
        $reservation = $this->book();
        $before = $reservation->fresh()->toArray();
        $this->actingAs($this->staff)->patch(route('reservations.status', $reservation), ['status'=>'cancelada', 'user_id'=>$this->staff->id, 'machine_id'=>999, 'garment_count'=>40, 'starts_at'=>'2030-01-01', 'date'=>'2030-01-01'])->assertSessionHasErrors(['user_id','machine_id','garment_count','starts_at','date']);
        $this->assertSame($before, $reservation->fresh()->toArray());
        $this->put('/reservas/'.$reservation->id, ['garment_count'=>40])->assertNotFound();
        $this->delete('/reservas/'.$reservation->id)->assertNotFound();
    }
    public function test_states_cannot_be_skipped_reopened_or_started_early(): void
    {
        $reservation = $this->book();
        foreach (['en_proceso','finalizada','listo','entregado','cancelado'] as $status) $this->actingAs($this->staff)->patch(route('reservations.status', $reservation), ['status'=>$status])->assertSessionHasErrors('status');
        $reservation->update(['status'=>'finalizada']);
        $this->patch(route('reservations.status', $reservation), ['status'=>'pendiente'])->assertSessionHasErrors('status');
    }
    public function test_staff_can_cancel_a_pending_or_in_progress_reservation(): void
    {
        foreach (['pendiente','en_proceso'] as $status) {
            $reservation = $this->book();
            $reservation->update(['status'=>$status]);
            $this->actingAs($this->staff)->patch(route('reservations.status', $reservation), ['status'=>'cancelada'])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame('cancelada', $reservation->fresh()->status);
        }
        $this->assertDatabaseCount('reservation_slots', 0);
    }
    public function test_invalid_slots_are_rejected(): void
    {
        foreach ([['date'=>'2026-09-14'],['date'=>'2026-11-01'],['time'=>'07:00'],['time'=>'20:00'],['time'=>'10:30']] as $override) $this->actingAs($this->student)->post('/reservas', array_replace($this->payload(), $override))->assertSessionHasErrors();
        $this->assertDatabaseCount('reservations', 0);
    }
    public function test_maintenance_deleted_machines_and_dryers_cannot_be_booked(): void
    {
        $this->machine->update(['status'=>'mantenimiento']);
        $this->actingAs($this->student)->post('/reservas', $this->payload())->assertSessionHasErrors('machine_id');
        $this->machine->update(['status'=>'disponible','type'=>'secadora']);
        $this->post('/reservas', $this->payload())->assertSessionHasErrors('machine_id');
        $this->machine->delete();
        $this->post('/reservas', $this->payload())->assertNotFound();
        $this->assertDatabaseCount('reservations', 0);
    }
    public function test_availability_and_all_views_and_filters_work(): void
    {
        $reservation = $this->book();
        $this->get('/ingresar')->assertOk(); $this->get('/registro')->assertOk();
        foreach (['/inicio','/reservar','/reservas','/reservas?status=pendiente'] as $url) $this->actingAs($this->student)->get($url)->assertOk();
        $this->get('/reservar?date=2026-09-16&time=10:00')->assertSee('Ocupada en este turno');
        $this->get('/reservas?status=cancelada')->assertDontSee($reservation->code);
        $this->get('/reservas?date=2026-09-17')->assertDontSee($reservation->code);
        $this->get('/reservas?search=Lavadora')->assertSee($reservation->code);
        foreach (['/inicio','/reservas','/maquinas'] as $url) $this->actingAs($this->staff)->get($url)->assertOk();
        $this->post('/maquinas', [])->assertStatus(405);
        $this->put('/maquinas/'.$this->machine->id, [])->assertNotFound();
        $this->delete('/maquinas/'.$this->machine->id)->assertNotFound();
    }
    public function test_no_cancellation_button_after_start(): void
    {
        $this->book();
        $this->travelTo(Carbon::parse('2026-09-16 10:00'));
        $this->actingAs($this->student)->get('/reservas')->assertSee('Horario iniciado')->assertDontSee('>Cancelar</button>', false);
    }
    public function test_database_unique_constraint_protects_slot_without_service(): void
    {
        $reservation = $this->book();
        $other = Reservation::create(['user_id'=>$this->student->id,'machine_id'=>$this->machine->id,'starts_at'=>$reservation->starts_at,'ends_at'=>$reservation->ends_at,'garment_count'=>3]);
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        DB::table('reservation_slots')->insert(['reservation_id'=>$other->id,'machine_id'=>$this->machine->id,'starts_at'=>$reservation->starts_at]);
    }
    public function test_migration_preserves_legacy_reservations_without_inventing_quantity(): void
    {
        $reservation = $this->book();
        $migration = require database_path('migrations/2026_09_15_000001_align_reservations_with_exam.php');
        $migration->down();
        DB::table('reservations')->where('id', $reservation->id)->update(['status'=>'listo']);
        $migration->up();
        $this->assertDatabaseHas('reservations', ['id'=>$reservation->id,'status'=>'finalizada','garment_count'=>null]);
        $this->assertDatabaseCount('reservation_slots', 1);
        $this->actingAs($this->student)->get('/reservas')->assertSee('No registrada');
    }
}
