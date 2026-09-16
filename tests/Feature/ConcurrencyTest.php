<?php
namespace Tests\Feature;
use App\Models\{User, Machine, Reservation};
use Illuminate\Support\Facades\{Artisan, DB};
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    private string $databaseFile;
    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseFile = tempnam(sys_get_temp_dir(), 'qw-concurrency-');
        config(['database.connections.sqlite.database'=>$this->databaseFile]);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force'=>true]);
    }
    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        @unlink($this->databaseFile);
        parent::tearDown();
    }
    private function race(array $jobs): array
    {
        $barrier = $this->databaseFile.'.start';
        $processes = [];
        $env = array_merge(getenv(), ['APP_ENV'=>'testing','DB_CONNECTION'=>'sqlite','DB_DATABASE'=>$this->databaseFile,'DB_URL'=>'','CACHE_STORE'=>'array','SESSION_DRIVER'=>'array']);
        foreach ($jobs as [$userId, $machineId, $start]) {
            $pipes = [];
            $process = proc_open([PHP_BINARY,'-c',php_ini_loaded_file(),base_path('tests/concurrency-worker.php'),(string)$userId,(string)$machineId,$start,$barrier], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, base_path(), $env);
            $this->assertIsResource($process); fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        touch($barrier);
        $results = [];
        foreach ($processes as [$process, $pipes]) {
            $results[] = trim(stream_get_contents($pipes[1]));
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            $this->assertSame(0, proc_close($process), $error);
        }
        @unlink($barrier);
        return $results;
    }
    public function test_simultaneous_students_cannot_claim_same_slot(): void
    {
        $machine = Machine::create(['name'=>'Lavadora concurrente','capacity'=>8,'location'=>'Campus','type'=>'lavadora','status'=>'disponible']);
        $jobs = [];
        foreach (range(1,4) as $n) $jobs[] = [User::factory()->create()->id,$machine->id,today()->addDay()->setTime(10,0)->toDateTimeString()];
        $results = $this->race($jobs);
        $this->assertSame(1, count(array_filter($results, fn ($r) => $r === 'BOOKED')));
        $this->assertSame(3, count(array_filter($results, fn ($r) => $r === 'REJECTED')));
        $this->assertSame(1, Reservation::count());
        $this->assertSame(1, DB::table('reservation_slots')->count());
    }
    public function test_simultaneous_bookings_cannot_exceed_student_quota(): void
    {
        $student = User::factory()->create();
        $jobs = [];
        foreach (range(1,4) as $n) {
            $machine = Machine::create(['name'=>'Lavadora '.$n,'capacity'=>8,'location'=>'Campus','type'=>'lavadora','status'=>'disponible']);
            $jobs[] = [$student->id,$machine->id,today()->addDay()->setTime(10,0)->toDateTimeString()];
        }
        $results = $this->race($jobs);
        $this->assertSame(3, count(array_filter($results, fn ($r) => $r === 'BOOKED')));
        $this->assertSame(1, count(array_filter($results, fn ($r) => $r === 'REJECTED')));
        $this->assertSame(3, $student->reservations()->whereIn('status', Reservation::ACTIVE)->count());
    }
}
