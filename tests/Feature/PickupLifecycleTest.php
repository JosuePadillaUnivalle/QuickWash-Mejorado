<?php
namespace Tests\Feature;

use App\Models\{Machine, Reservation, User};
use App\Services\{BookingService, ReservationLifecycle};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PickupLifecycleTest extends TestCase
{
    use RefreshDatabase;
    private User $student;
    private Machine $machine;
    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-17 15:00:00'));
        $this->student = User::factory()->create();
        $this->machine = Machine::create(['name'=>'Lavadora reloj', 'type'=>'lavadora', 'capacity'=>8, 'location'=>'Campus', 'status'=>'disponible']);
        $this->reservation = app(BookingService::class)->book($this->student, $this->machine->id, Carbon::parse('2026-09-17 16:00'), 10);
    }

    public static function boundaries(): array
    {
        return [['15:59:59','pendiente',false], ['16:00:00','en_proceso',false], ['16:59:59','en_proceso',false], ['17:00:00','esperando_recogida',true], ['23:00:00','esperando_recogida',true]];
    }

    #[DataProvider('boundaries')]
    public function test_clock_boundaries_without_browser_or_staff(string $time, string $status, bool $collectable): void
    {
        $this->travelTo(Carbon::parse('2026-09-17 '.$time));
        $this->artisan('quickwash:sync')->assertExitCode(0);
        $this->assertSame($status, $this->reservation->fresh()->status);
        $response = $this->actingAs($this->student)->get('/reservas');
        $collectable ? $response->assertSee('>Recogido</button>', false) : $response->assertDontSee('>Recogido</button>', false);
        $this->assertNull($this->reservation->fresh()->collected_at);
    }

    public function test_only_owner_can_collect_and_not_before_end(): void
    {
        $url = route('reservations.collect', $this->reservation);
        $this->patch($url)->assertRedirect('/ingresar');
        $this->actingAs($this->student)->patch($url)->assertSessionHasErrors('status');
        $this->travelTo(Carbon::parse('2026-09-17 16:59:59'));
        $this->patch($url)->assertSessionHasErrors('status');
        $this->travelTo(Carbon::parse('2026-09-17 17:00:00'));
        $this->actingAs(User::factory()->create())->patch($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role'=>'personal']))->patch($url)->assertForbidden();
        $this->assertSame('esperando_recogida', $this->reservation->fresh()->status);
        $this->actingAs($this->student)->patch($url)->assertSessionHasNoErrors();
        $this->assertSame('finalizada', $this->reservation->fresh()->status);
    }

    public function test_pickup_is_idempotent_and_keeps_history_and_quota(): void
    {
        $this->travelTo(Carbon::parse('2026-09-17 17:00'));
        $url = route('reservations.collect', $this->reservation);
        $this->actingAs($this->student)->patch($url)->assertSessionHasNoErrors();
        $collected = $this->reservation->fresh()->collected_at;
        $this->travel(10)->minutes();
        $this->patch($url)->assertSessionHasNoErrors();
        $this->assertTrue($collected->equalTo($this->reservation->fresh()->collected_at));
        $this->assertSame(0, $this->student->reservations()->whereIn('status', Reservation::ACTIVE)->count());
        $this->assertDatabaseCount('reservation_slots', 1);
    }

    public function test_overdue_pickup_blocks_new_bookings_until_collection(): void
    {
        $this->travelTo(Carbon::parse('2026-09-17 18:00'));
        $payload = ['machine_id'=>$this->machine->id,'date'=>'2026-09-18','time'=>'10:00','garment_count'=>5];
        $this->actingAs($this->student)->post('/reservas', $payload)->assertSessionHasErrors('machine_id');
        $this->get('/reservar?date=2026-09-18&time=10:00')->assertSee('Esperando recogida')->assertDontSee('Disponible en este turno');
        $this->patch(route('reservations.collect', $this->reservation))->assertSessionHasNoErrors();
        $this->post('/reservas', $payload)->assertSessionHasNoErrors();
    }

    public function test_late_pickup_delays_next_wash_without_shortening_or_overlap(): void
    {
        $second = app(BookingService::class)->book(User::factory()->create(), $this->machine->id, Carbon::parse('2026-09-17 17:00'), 5);
        $third = app(BookingService::class)->book(User::factory()->create(), $this->machine->id, Carbon::parse('2026-09-17 18:00'), 5);
        $this->travelTo(Carbon::parse('2026-09-17 18:30'));
        app(ReservationLifecycle::class)->sync();
        $this->assertSame('esperando_recogida', $this->reservation->fresh()->status);
        $this->assertSame('pendiente', $second->fresh()->status);
        app(ReservationLifecycle::class)->collect($this->reservation, $this->student);
        $this->assertSame('en_proceso', $second->fresh()->status);
        $this->assertSame('18:30', $second->fresh()->processing_started_at->format('H:i'));
        $this->assertSame('19:30', $second->fresh()->processing_ends_at->format('H:i'));
        $this->assertSame('17:00', $second->fresh()->starts_at->format('H:i'));
        $this->assertSame('pendiente', $third->fresh()->status);
        $this->travelTo(Carbon::parse('2026-09-17 19:30'));
        app(ReservationLifecycle::class)->collect($second, $second->user);
        $this->assertSame('en_proceso', $third->fresh()->status);
        $this->assertSame('20:30', $third->fresh()->processing_ends_at->format('H:i'));
    }

    public function test_three_waiting_pickups_still_consume_quota(): void
    {
        foreach (range(2,3) as $n) {
            $machine = $this->machine->replicate(); $machine->name .= $n; $machine->save();
            app(BookingService::class)->book($this->student, $machine->id, Carbon::parse('2026-09-17 16:00'), 5);
        }
        $this->travelTo(Carbon::parse('2026-09-17 17:00'));
        app(ReservationLifecycle::class)->sync();
        $this->assertSame(3, $this->student->reservations()->where('status','esperando_recogida')->count());
        $free = $this->machine->replicate(); $free->name = 'Libre'; $free->save();
        $this->actingAs($this->student)->post('/reservas', ['machine_id'=>$free->id,'date'=>'2026-09-18','time'=>'10:00','garment_count'=>5])->assertSessionHasErrors('slot');
    }

    public function test_cancelled_and_historical_finished_records_are_not_reopened(): void
    {
        $this->reservation->update(['status'=>'cancelada']);
        $this->travelTo(Carbon::parse('2026-09-18 12:00'));
        app(ReservationLifecycle::class)->sync();
        $this->assertSame('cancelada', $this->reservation->fresh()->status);
        $this->actingAs($this->student)->patch(route('reservations.collect', $this->reservation))->assertSessionHasErrors('status');
        $this->reservation->update(['status'=>'finalizada']);
        app(ReservationLifecycle::class)->sync();
        $this->assertSame('finalizada', $this->reservation->fresh()->status);
        $this->assertNull($this->reservation->fresh()->collected_at);
    }
}
